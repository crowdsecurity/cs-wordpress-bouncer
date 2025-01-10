<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine;

use CrowdSec\LapiClient\Bouncer;
use CrowdSec\LapiClient\ClientException;
use CrowdSec\LapiClient\Constants as LapiConstants;
use CrowdSec\LapiClient\TimeoutException;
use CrowdSec\RemediationEngine\CacheStorage\AbstractCache;
use CrowdSec\RemediationEngine\CacheStorage\CacheStorageException;
use CrowdSec\RemediationEngine\Configuration\Lapi as LapiRemediationConfig;
use IPLib\Address\Type;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;

class LapiRemediation extends AbstractRemediation
{
    /** @var array The list of each known LAPI remediation, sorted by priority */
    public const ORDERED_REMEDIATIONS = [Constants::REMEDIATION_BAN, Constants::REMEDIATION_CAPTCHA];

    /**
     * @var Bouncer
     */
    private $client;
    /**
     * @var array|null
     */
    private $scopes;

    public function __construct(
        array $configs,
        Bouncer $client,
        AbstractCache $cacheStorage,
        ?LoggerInterface $logger = null
    ) {
        $this->configure($configs);
        $this->client = $client;
        parent::__construct($this->configs, $cacheStorage, $logger);
    }

    /**
     *  This method aims to be used synchronously in the remediation process,
     *  after a call to the getIpRemediation method.
     *  We don't ask for cached LAPI decisions, as it is done by the getIpRemediation method.
     *  If you want to use this method alone, you should call the getAllCachedDecisions method before.
     *
     * @throws CacheException
     * @throws ClientException
     * @throws InvalidArgumentException
     */
    public function getAppSecRemediation(array $headers, string $rawBody = ''): array
    {
        $clean = [
            Constants::REMEDIATION_KEY => Constants::REMEDIATION_BYPASS,
            Constants::ORIGIN_KEY => AbstractCache::CLEAN_APPSEC,
        ];
        if (!$this->validateAppSecHeaders($headers)) {
            return $clean;
        }
        if (!$this->validateRawBody($rawBody)) {
            $action = $this->getConfig('appsec_body_size_exceeded_action') ?? Constants::APPSEC_ACTION_HEADERS_ONLY;
            $this->logger->debug('Action to be taken if maximum size is exceeded', [
                'type' => 'LAPI_REM_APPSEC_BODY_SIZE_EXCEEDED',
                'action' => $action,
            ]);
            switch ($action) {
                case Constants::APPSEC_ACTION_BLOCK:
                    return [
                        Constants::REMEDIATION_KEY => Constants::REMEDIATION_BAN,
                        Constants::ORIGIN_KEY => Constants::ORIGIN_APPSEC,
                    ];
                case Constants::APPSEC_ACTION_ALLOW:
                    return [
                        Constants::REMEDIATION_KEY => $clean[Constants::REMEDIATION_KEY],
                        Constants::ORIGIN_KEY => $clean[Constants::ORIGIN_KEY],
                    ];
                    // Default to headers only action
                default:
                    $rawBody = '';
                    break;
            }
        }
        try {
            $rawAppSecDecision = $this->client->getAppSecDecision($headers, $rawBody);
        } catch (TimeoutException $e) {
            $this->logger->error('Timeout while retrieving AppSec decision', [
                'type' => 'LAPI_REM_APPSEC_TIMEOUT',
                'exception' => $e,
            ]);

            // Early return for AppSec fallback remediation
            $remediation = $this->getConfig('appsec_fallback_remediation') ?? Constants::REMEDIATION_BYPASS;
            $origin = Constants::REMEDIATION_BYPASS === $remediation ?
                $clean[Constants::ORIGIN_KEY] :
                Constants::ORIGIN_APPSEC;

            return [
                Constants::REMEDIATION_KEY => $remediation,
                Constants::ORIGIN_KEY => $origin,
            ];
        }
        $rawRemediation = $this->parseAppSecDecision($rawAppSecDecision);
        if (Constants::REMEDIATION_BYPASS === $rawRemediation) {
            return $clean;
        }
        // We only set required indexes for the processCachedDecisions method
        $fakeCachedDecisions = [[
            AbstractCache::INDEX_MAIN => $rawRemediation,
            AbstractCache::INDEX_ORIGIN => Constants::ORIGIN_APPSEC,
        ]];

        return $this->processCachedDecisions($fakeCachedDecisions);
    }

    public function getClient(): Bouncer
    {
        return $this->client;
    }

    /**
     * Retrieve the remediation and its origin for a given IP.
     *
     * It will first check the cache for the IP decisions.
     * If no decisions are found, it will call LAPI to get the decisions.
     * The decisions are then stored in the cache.
     * The remediation is then processed and returned.
     *
     * @throws CacheStorageException
     * @throws InvalidArgumentException
     * @throws RemediationException
     * @throws CacheException|ClientException
     */
    public function getIpRemediation(string $ip): array
    {
        $clean = [
            Constants::REMEDIATION_KEY => Constants::REMEDIATION_BYPASS,
            Constants::ORIGIN_KEY => AbstractCache::CLEAN,
        ];
        $country = $this->getCountryForIp($ip);
        $cachedDecisions = $this->getAllCachedDecisions($ip, $country);
        $this->logger->debug('Cache result', [
            'type' => 'LAPI_REM_CACHED_DECISIONS',
            'ip' => $ip,
            'result' => $cachedDecisions ? 'hit' : 'miss',
        ]);
        if (!$cachedDecisions) {
            // In stream_mode, we do not store this bypass, and we do not call LAPI directly
            if ($this->getConfig('stream_mode')) {
                return $clean;
            }
            // In live mode, ask LAPI (Retrieve Ip AND Range scoped decisions)
            $this->storeFirstCall(time());
            $rawIpDecisions = $this->client->getFilteredDecisions(['ip' => $ip]);
            $ipDecisions = $this->convertRawDecisionsToDecisions($rawIpDecisions);
            // IPV6 range scoped decisions are not yet stored in cache, so we store it as IP scoped decisions
            $ipDecisions = $this->handleIpV6RangeDecisions($ipDecisions);
            $countryDecisions = [];
            if ($country) {
                // Retrieve country scoped decisions
                $rawCountryDecisions = $this->client->getFilteredDecisions(
                    ['scope' => Constants::SCOPE_COUNTRY, 'value' => $country]
                );
                $countryDecisions = $this->convertRawDecisionsToDecisions($rawCountryDecisions);
            }
            $liveDecisions = array_merge($ipDecisions, $countryDecisions);

            $finalDecisions = $liveDecisions ?:
                $this->convertRawDecisionsToDecisions([[
                    'scope' => Constants::SCOPE_IP,
                    'value' => $ip,
                    'type' => Constants::REMEDIATION_BYPASS,
                    'origin' => AbstractCache::CLEAN,
                    'duration' => sprintf('%ss', (int) $this->getConfig('clean_ip_cache_duration')),
                ]]);
            // Store decision(s) even if bypass
            $stored = $this->storeDecisions($finalDecisions);
            $cachedDecisions = !empty($stored[AbstractCache::STORED]) ? $stored[AbstractCache::STORED] : [];
        }

        return $this->processCachedDecisions($cachedDecisions);
    }

    /**
     * Push usage metrics to LAPI.
     *
     * The metrics are built from the cache and then sent to LAPI.
     * The cache is then updated to reflect the metrics sent.
     * Returns the metrics items sent to LAPI.
     *
     * @throws CacheException
     * @throws ClientException
     * @throws InvalidArgumentException
     */
    public function pushUsageMetrics(
        string $bouncerName,
        string $bouncerVersion,
        string $bouncerType = LapiConstants::METRICS_TYPE
    ): array {
        $cacheConfigItem = $this->cacheStorage->getItem(AbstractCache::CONFIG);
        $cacheConfig = $cacheConfigItem->isHit() ? $cacheConfigItem->get() : [];
        $start = $cacheConfig[AbstractCache::FIRST_LAPI_CALL] ?? 0;
        $now = time();
        $lastSent = $cacheConfig[AbstractCache::LAST_METRICS_SENT] ?? $start;
        // Updating the "origins count" metrics in cache is the responsibility of the bouncer.
        $originsCount = $this->getOriginsCount();
        $build = $this->buildMetricsItems($originsCount);
        $metricsItems = $build['items'] ?? [];
        $originsToUpdate = $build['origins'] ?? [];
        if (empty($metricsItems)) {
            $this->logger->info('No metrics to send', [
                'type' => 'LAPI_REM_NO_METRICS',
            ]);

            return [];
        }

        $properties = [
            'name' => $bouncerName,
            'type' => $bouncerType,
            'version' => $bouncerVersion,
            'utc_startup_timestamp' => $start,
        ];
        $meta = [
            'window_size_seconds' => max(0, $now - $lastSent),
            'utc_now_timestamp' => $now,
        ];
        $this->logger->debug('Metrics to build', [
            'type' => 'LAPI_REM_METRICS',
            'items' => $metricsItems,
            'properties' => $properties,
            'meta' => $meta,
        ]);

        $metrics = $this->client->buildUsageMetrics($properties, $meta, $metricsItems);

        $this->client->pushUsageMetrics($metrics);

        // Decrement the count of each origin/remediation
        foreach ($originsToUpdate as $origin => $remediationCount) {
            foreach ($remediationCount as $remediation => $delta) {
                // We update the count of each origin/remediation, one by one
                // because we want to handle the case where an origin/remediation/count has been updated
                // between the time we get the count and the time we update it
                // $delta is negative, so we decrement the count
                $this->updateMetricsOriginsCount($origin, $remediation, $delta);
            }
        }

        $this->storeMetricsLastSent($now);

        return $metrics;
    }

    /**
     * Refresh the decisions from LAPI.
     *
     * This method is only available in stream mode.
     * Depending on the warmup status, it will either process a startup or a regular refresh.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @throws CacheException
     * @throws CacheStorageException
     * @throws ClientException
     * @throws InvalidArgumentException
     */
    public function refreshDecisions(): array
    {
        if (!$this->getConfig('stream_mode')) {
            $this->logger->info('Decisions refresh is only available in stream mode', [
                'type' => 'LAPI_REM_REFRESH_DECISIONS',
            ]);

            return [
                self::CS_NEW => 0,
                self::CS_DEL => 0,
            ];
        }

        $filter = ['scopes' => implode(',', $this->getScopes())];

        if (!$this->isWarm()) {
            return $this->warmUp($filter);
        }

        return $this->getStreamDecisions(false, $filter);
    }

    private function buildMetricsItems(array $originsCount): array
    {
        $metricsItems = [];
        $processed = 0;
        $originsToUpdate = [];
        foreach ($originsCount as $origin => $remediationCount) {
            foreach ($remediationCount as $remediation => $count) {
                if ($count <= 0) {
                    continue;
                }
                // Count all processed metrics, even bypass ones
                $processed += $count;
                // Prepare data to update origins count item after processing
                $originsToUpdate[$origin][$remediation] = -$count;
                if (Constants::REMEDIATION_BYPASS === $remediation) {
                    continue;
                }
                // Create "dropped" metrics (all that is not a bypass)
                $metricsItems[] = [
                    'name' => 'dropped',
                    'value' => $count,
                    'unit' => 'request',
                    'labels' => [
                        'origin' => $origin,
                        'remediation' => $remediation,
                    ],
                ];
            }
        }
        if ($processed > 0) {
            $metricsItems[] = [
                'name' => 'processed',
                'value' => $processed,
                'unit' => 'request',
            ];
        }

        return ['items' => $metricsItems, 'origins' => $originsToUpdate];
    }

    /**
     * Process and validate input configurations.
     */
    private function configure(array $configs): void
    {
        $configuration = new LapiRemediationConfig();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
    }

    /**
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    private function getFirstCall(): int
    {
        $cacheConfigItem = $this->cacheStorage->getItem(AbstractCache::CONFIG);
        $cacheConfig = $cacheConfigItem->isHit() ? $cacheConfigItem->get() : [];

        return $cacheConfig[AbstractCache::FIRST_LAPI_CALL] ?? 0;
    }

    private function getScopes(): array
    {
        if (null === $this->scopes) {
            $finalScopes = [Constants::SCOPE_IP, Constants::SCOPE_RANGE];
            $geolocConfigs = (array) $this->getConfig('geolocation');
            if (!empty($geolocConfigs['enabled'])) {
                $finalScopes[] = Constants::SCOPE_COUNTRY;
            }
            $this->scopes = $finalScopes;
        }

        return $this->scopes;
    }

    /**
     * @throws CacheException
     * @throws CacheStorageException
     * @throws ClientException
     * @throws InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function getStreamDecisions(bool $startup = false, array $filter = []): array
    {
        $this->storeFirstCall(time());
        $rawDecisions = $this->client->getStreamDecisions($startup, $filter);
        $newDecisions = $this->convertRawDecisionsToDecisions($rawDecisions[self::CS_NEW] ?? []);
        $deletedDecisions = $this->convertRawDecisionsToDecisions($rawDecisions[self::CS_DEL] ?? []);
        $stored = $this->storeDecisions($newDecisions);
        $removed = $this->removeDecisions($deletedDecisions);

        $result = [
            self::CS_NEW => $stored[AbstractCache::DONE] ?? 0,
            self::CS_DEL => $removed[AbstractCache::DONE] ?? 0,
        ];

        $this->logger->info('Retrieved stream decisions', [
            'type' => 'LAPI_REM_STREAM_DECISIONS',
            'startup' => $startup,
            'filter' => $filter,
            'result' => $result,
        ]);

        return $result;
    }

    private function handleIpV6RangeDecisions(array $decisions): array
    {
        /** @var Decision $decision */
        foreach ($decisions as $index => $decision) {
            if (Constants::SCOPE_RANGE === $decision->getScope()) {
                $rangeIp = preg_replace('#^(.*)/(.*)$#', '$1', $decision->getValue());
                if (Type::T_IPv6 === $this->getIpType($rangeIp)) {
                    $decision->setValue($rangeIp)->setScope(Constants::SCOPE_IP);
                    $decisions[$index] = $decision;
                }
            }
        }

        return $decisions;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function isWarm(): bool
    {
        $cacheConfigItem = $this->cacheStorage->getItem(AbstractCache::CONFIG);
        $cacheConfig = $cacheConfigItem->get();

        return \is_array($cacheConfig) && isset($cacheConfig[AbstractCache::WARMUP])
               && true === $cacheConfig[AbstractCache::WARMUP];
    }

    private function parseAppSecDecision(array $rawAppSecDecision): string
    {
        if (!isset($rawAppSecDecision['action'])) {
            return Constants::REMEDIATION_BYPASS;
        }

        return Constants::APPSEC_ACTION_ALLOW === $rawAppSecDecision['action'] ?
            Constants::REMEDIATION_BYPASS :
            $rawAppSecDecision['action'];
    }

    /**
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    private function storeFirstCall(int $timestamp): void
    {
        $firstCall = $this->getFirstCall();
        if (0 !== $firstCall) {
            return;
        }
        $content = [AbstractCache::FIRST_LAPI_CALL => $timestamp];
        $this->logger->info(
            'Flag LAPI first call',
            [
                'type' => 'LAPI_REM_CACHE_FIRST_CALL',
                'time' => $timestamp,
            ]
        );

        $this->cacheStorage->upsertItem(
            AbstractCache::CONFIG,
            $content,
            0,
            [AbstractCache::CONFIG]
        );
    }

    /**
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    private function storeMetricsLastSent(int $timestamp): void
    {
        $content = [AbstractCache::LAST_METRICS_SENT => $timestamp];
        $this->logger->debug(
            'Flag metrics last sent',
            [
                'type' => 'LAPI_REM_CACHE_METRICS_LAST_SENT',
                'time' => $timestamp,
            ]
        );

        $this->cacheStorage->upsertItem(
            AbstractCache::CONFIG,
            $content,
            0,
            [AbstractCache::CONFIG]
        );
    }

    private function validateAppSecHeaders(array $headers): bool
    {
        if (
            empty($headers[Constants::HEADER_APPSEC_IP])
            || empty($headers[Constants::HEADER_APPSEC_URI])
            || empty($headers[Constants::HEADER_APPSEC_VERB])
        ) {
            $this->logger->error('Missing or empty required AppSec header', [
                'type' => 'LAPI_REM_APPSEC_MISSING_HEADER',
                'headers' => $headers,
            ]);

            return false;
        }

        return true;
    }

    private function validateRawBody(string $rawBody): bool
    {
        // rawBody length is in bytes, so we convert the max size in bytes
        $maxBodySize = $this->getConfig('appsec_max_body_size_kb') * 1024;
        $rawBodySize = strlen($rawBody);

        if ($rawBodySize > $maxBodySize) {
            $this->logger->warning('Request body size exceeded', [
                'type' => 'LAPI_REM_APPSEC_BODY_SIZE_EXCEEDED',
                'size' => $rawBodySize,
                'max_size' => $maxBodySize,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @throws ClientException
     * @throws InvalidArgumentException
     * @throws CacheException
     * @throws CacheStorageException
     */
    private function warmUp(array $filter): array
    {
        $this->cacheStorage->clear();
        $result = $this->getStreamDecisions(true, $filter);
        // Store the fact that the cache has been warmed up.
        $this->logger->info('Flag cache warmup', ['type' => 'LAPI_REM_CACHE_WARMUP']);
        $this->cacheStorage->upsertItem(
            AbstractCache::CONFIG,
            [AbstractCache::WARMUP => true],
            0,
            [AbstractCache::CONFIG]
        );

        return $result;
    }
}
