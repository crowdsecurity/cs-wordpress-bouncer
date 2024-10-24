<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine;

use CrowdSec\LapiClient\Bouncer;
use CrowdSec\LapiClient\ClientException;
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

    public function getClient(): Bouncer
    {
        return $this->client;
    }

    /**
     * @throws CacheStorageException
     * @throws InvalidArgumentException
     * @throws RemediationException
     * @throws CacheException|ClientException
     */
    public function getIpRemediation(string $ip): string
    {
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
                $this->updateRemediationOriginCount(AbstractCache::CLEAN);

                return Constants::REMEDIATION_BYPASS;
            }
            // In live mode, ask LAPI (Retrieve Ip AND Range scoped decisions)
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

    private function parseAppSecDecision(array $rawAppSecDecision): string
    {
        if (!isset($rawAppSecDecision['action'])) {
            return Constants::REMEDIATION_BYPASS;
        }

        return Constants::APPSEC_ACTION_ALLOW === $rawAppSecDecision['action'] ?
            Constants::REMEDIATION_BYPASS :
            $rawAppSecDecision['action'];
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
     *  This method aims to be used synchronously in the remediation process,
     *  after a call to the getIpRemediation method.
     *  We don't ask for cached LAPI decisions, as it is done by the getIpRemediation method.
     *  If you want to use this method alone, you should call the getAllCachedDecisions method before.
     *
     * @throws CacheException
     * @throws ClientException
     * @throws InvalidArgumentException
     */
    public function getAppSecRemediation(array $headers, string $rawBody = ''): string
    {
        if (!$this->validateAppSecHeaders($headers)) {
            return Constants::REMEDIATION_BYPASS;
        }
        if (!$this->validateRawBody($rawBody)) {
            $action = $this->getConfig('appsec_body_size_exceeded_action') ?? Constants::APPSEC_ACTION_HEADERS_ONLY;
            $this->logger->debug('Action to be taken if maximum size is exceeded', [
                'type' => 'LAPI_REM_APPSEC_BODY_SIZE_EXCEEDED',
                'action' => $action,
            ]);
            switch ($action) {
                case Constants::APPSEC_ACTION_BLOCK:
                    return Constants::REMEDIATION_BAN;
                case Constants::APPSEC_ACTION_ALLOW:
                    return Constants::REMEDIATION_BYPASS;
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
            return $this->getConfig('appsec_fallback_remediation') ?? Constants::REMEDIATION_BYPASS;
        }
        $rawRemediation = $this->parseAppSecDecision($rawAppSecDecision);
        if (Constants::REMEDIATION_BYPASS === $rawRemediation) {
            $this->updateRemediationOriginCount(AbstractCache::CLEAN_APPSEC);

            return Constants::REMEDIATION_BYPASS;
        }
        // We only set required indexes for the processCachedDecisions method
        $fakeCachedDecisions = [[
            AbstractCache::INDEX_MAIN => $rawRemediation,
            AbstractCache::INDEX_ORIGIN => Constants::ORIGIN_APPSEC,
        ]];

        return $this->processCachedDecisions($fakeCachedDecisions);
    }

    /**
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

    /**
     * Process and validate input configurations.
     */
    private function configure(array $configs): void
    {
        $configuration = new LapiRemediationConfig();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
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
