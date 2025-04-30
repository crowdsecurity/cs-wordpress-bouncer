<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine;

use CrowdSec\CapiClient\ClientException;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\RemediationEngine\CacheStorage\AbstractCache;
use CrowdSec\RemediationEngine\CacheStorage\CacheStorageException;
use CrowdSec\RemediationEngine\Configuration\Capi as CapiRemediationConfig;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;

class CapiRemediation extends AbstractRemediation
{
    /** @var array The list of each known CAPI remediation, sorted by priority */
    public const ORDERED_REMEDIATIONS = [Constants::REMEDIATION_BAN];
    /**
     * @var Watcher
     */
    private $client;

    public function __construct(
        array $configs,
        Watcher $client,
        AbstractCache $cacheStorage,
        ?LoggerInterface $logger = null
    ) {
        // Force stream mode for CAPI remediation
        $configs['stream_mode'] = true;
        $this->configure($configs);
        $this->client = $client;
        parent::__construct($this->configs, $cacheStorage, $logger);
    }

    public function getClient(): Watcher
    {
        return $this->client;
    }

    /**
     * @throws CacheStorageException
     * @throws InvalidArgumentException
     * @throws RemediationException|CacheException
     */
    public function getIpRemediation(string $ip): array
    {
        $clean = [
            Constants::REMEDIATION_KEY => Constants::REMEDIATION_BYPASS,
            Constants::ORIGIN_KEY => AbstractCache::CLEAN,
        ];
        $cachedDecisions = $this->getAllCachedDecisions($ip, $this->getCountryForIp($ip));
        if (!$cachedDecisions) {
            $this->logger->debug('There is no cached decision', [
                'type' => 'CAPI_REM_NO_CACHED_DECISIONS',
                'ip' => $ip,
            ]);
            // As CAPI is always in stream_mode, we do not store this bypass

            return $clean;
        }

        return $this->processCachedDecisions($cachedDecisions);
    }

    /**
     * @throws CacheStorageException
     * @throws InvalidArgumentException
     * @throws CacheException|ClientException
     */
    public function refreshDecisions(): array
    {
        $rawDecisions = $this->client->getStreamDecisions();
        $newDecisions = $this->convertRawCapiDecisionsToDecisions($rawDecisions[self::CS_NEW] ?? []);
        $deletedDecisions = $this->convertRawCapiDecisionsToDecisions($rawDecisions[self::CS_DEL] ?? []);
        $listDecisions = $this->handleListDecisions($rawDecisions[self::CS_LINK][self::CS_BLOCK] ?? []);
        $allowListDecisions = $this->handleAllowListDecisions($rawDecisions[self::CS_LINK][self::CS_ALLOW] ?? []);

        $stored = $this->storeDecisions(array_merge(
            $newDecisions,
            $listDecisions,
            $allowListDecisions
        ));
        $removed = $this->removeDecisions($deletedDecisions);

        return [
            self::CS_NEW => $stored[AbstractCache::DONE] ?? 0,
            self::CS_DEL => $removed[AbstractCache::DONE] ?? 0,
        ];
    }

    /**
     * Process and validate input configurations.
     */
    private function configure(array $configs): void
    {
        $configuration = new CapiRemediationConfig();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
    }

    private function convertRawCapiDecisionsToDecisions(array $rawDecisions): array
    {
        $decisions = [];
        foreach ($rawDecisions as $rawDecision) {
            $capiDecisions = $rawDecision['decisions'] ?? [];
            $scope = $rawDecision['scope'] ?? null;
            foreach ($capiDecisions as $capiDecision) {
                // We exclude "new" Capi decision with a "0h" duration
                if (isset($capiDecision['duration']) && '0h' === $capiDecision['duration']) {
                    continue;
                }
                // Deleted decision contains only the value of the deleted decision (an IP, a range, etc)
                if (is_string($capiDecision)) {
                    // Duration is required but is not used to delete a decision. Thus, we set 0h
                    $capiDecision = ['value' => $capiDecision, 'duration' => '0h'];
                }
                $capiDecision['scope'] = $scope;
                $capiDecision['type'] = Constants::REMEDIATION_BAN;
                $capiDecision['origin'] = strtoupper(Constants::ORIGIN_CAPI); // CrowdSec convention is CAPI
                $decision = $this->convertRawDecision($capiDecision);
                if ($decision) {
                    $decisions[] = $decision;
                }
            }
        }

        return $decisions;
    }

    private function formatIfModifiedSinceHeader(int $timestamp): string
    {
        return gmdate('D, d M Y H:i:s \G\M\T', $timestamp);
    }

    private function getDurationInSeconds(string $futureDate): int
    {
        try {
            // Remove microseconds for better compatibility with older PHP versions
            $cleanDate = preg_replace('/\.\d{3,6}/', '', $futureDate);

            $expiration = new \DateTime($cleanDate, new \DateTimeZone('UTC'));
            $now = new \DateTime('now', new \DateTimeZone('UTC'));

            $duration = $expiration->getTimestamp() - $now->getTimestamp();

            return max(0, $duration);
        } catch (\Throwable $e) {
            // If parsing fails, return 0 as a fallback
            return 0;
        }
    }

    /**
     * @throws InvalidArgumentException|CacheException
     */
    private function handleAllowListDecisions(array $allowlists): array
    {
        $decisions = [];
        try {
            foreach ($allowlists as $allowlist) {
                $headers = [];
                if ($this->validateAllowlist($allowlist)) {
                    // The existence of the following indexes must be guaranteed by the validateAllowlist method
                    $listName = strtolower((string) $allowlist['name']);
                    $listId = (string) $allowlist['id'];
                    $url = (string) $allowlist['url'];
                    $origin = Constants::ORIGIN_LISTS;
                    $allowDecision = [
                        'type' => Constants::ALLOW_LIST_REMEDIATION,
                        'origin' => $origin,
                        'scenario' => $listName,
                    ];

                    $lastPullCacheKey = $this->getCacheStorage()->getCacheKey(
                        AbstractCache::ALLOW_LIST,
                        $listId
                    );

                    $lastPullItem = $this->getCacheStorage()->getItem($lastPullCacheKey);

                    $pullTime = time();
                    if ($lastPullItem->isHit()) {
                        $lastPullContent = $lastPullItem->get();
                        $headers = $this->handleAllowListPullHeaders($headers, $lastPullContent);
                    }

                    $listResponse = rtrim(
                        $this->client->getCapiHandler()->getListDecisions($url, $headers),
                        \PHP_EOL
                    );

                    if ($listResponse) {
                        $this->cacheStorage->upsertItem(
                            $lastPullCacheKey,
                            [
                                AbstractCache::LAST_PULL => $pullTime,
                            ],
                            0,
                            [AbstractCache::ALLOW_LIST, $listName]
                        );
                        $decisions = $this->handleAllowListResponse($listResponse, $allowDecision);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->info('Something went wrong during allowlists decisions process', [
                'type' => 'CAPI_REM_HANDLE_ALLOW_LIST_DECISIONS',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }

        return $decisions;
    }

    private function handleAllowListPullHeaders(array $headers, array $lastPullContent): array
    {
        if (isset($lastPullContent[AbstractCache::LAST_PULL])) {
            $headers['If-Modified-Since'] = $this->formatIfModifiedSinceHeader(
                (int) $lastPullContent[AbstractCache::LAST_PULL]
            );
        }

        return $headers;
    }

    private function handleAllowListResponse(string $listResponse, array $allowDecision): array
    {
        $decisions = [];
        $listedAllows = explode(\PHP_EOL, $listResponse);
        $this->logger->debug('Handle allowlists decisions', [
            'type' => 'CAPI_REM_HANDLE_ALLOW_LIST_DECISIONS',
            'list_count' => count($listedAllows),
        ]);
        foreach ($listedAllows as $listedAllow) {
            $decoded = json_decode($listedAllow, true);
            $allowDecision['value'] = $decoded['value'];
            $allowDecision['scope'] = $decoded['scope'];
            /*
             * This hardcoded value ill be overwritten below by the duration in the allowlist.
             * We have to set it to avoid an exception from the convertRawDecision method.
             */
            $allowDecision['duration'] = '1s';
            $decision = $this->convertRawDecision($allowDecision);

            if ($decision) {
                $durationInSeconds = isset($decoded['expiration']) ?
                    $this->getDurationInSeconds($decoded['expiration']) :
                    Constants::MAX_DURATION;

                $decision->setExpiresAt(time() + $durationInSeconds);
                $decisions[] = $decision;
            }
        }

        return $decisions;
    }

    /**
     * @throws InvalidArgumentException|CacheException
     */
    private function handleListDecisions(array $blocklists): array
    {
        $decisions = [];
        try {
            foreach ($blocklists as $blocklist) {
                $headers = [];
                if ($this->validateBlocklist($blocklist)) {
                    // The existence of the following indexes must be guaranteed by the validateBlocklist method
                    $scope = (string) $blocklist['scope'];
                    $duration = (string) $blocklist['duration'];
                    $type = (string) $blocklist['remediation'];
                    $listName = strtolower((string) $blocklist['name']);
                    $url = (string) $blocklist['url'];
                    $origin = Constants::ORIGIN_LISTS;
                    $blockDecision = [
                        'scope' => $scope,
                        'type' => $type,
                        'origin' => $origin,
                        'duration' => $duration,
                        'scenario' => $listName,
                    ];

                    $lastPullCacheKey = $this->getCacheStorage()->getCacheKey(
                        AbstractCache::LIST,
                        $listName
                    );

                    $lastPullItem = $this->getCacheStorage()->getItem($lastPullCacheKey);

                    $pullTime = time();
                    if ($lastPullItem->isHit()) {
                        $lastPullContent = $lastPullItem->get();
                        $frequency = $this->getConfig('refresh_frequency_indicator') ?? Constants::REFRESH_FREQUENCY;
                        $headers = $this->handleListPullHeaders($headers, $lastPullContent, $pullTime, (int)
                        $frequency);
                    }

                    $listResponse = rtrim(
                        $this->client->getCapiHandler()->getListDecisions($url, $headers),
                        \PHP_EOL
                    );

                    if ($listResponse) {
                        $duration = $this->parseDurationToSeconds($duration);
                        $this->cacheStorage->upsertItem(
                            $lastPullCacheKey,
                            [
                                AbstractCache::LAST_PULL => $pullTime,
                                AbstractCache::INDEX_EXP => $duration + $pullTime,
                            ],
                            $duration,
                            [AbstractCache::LIST, $listName]
                        );
                        $decisions = $this->handleListResponse($listResponse, $blockDecision);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->info('Something went wrong during blocklists decisions process', [
                'type' => 'CAPI_REM_HANDLE_LIST_DECISIONS',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }

        return $decisions;
    }

    private function handleListPullHeaders(array $headers, array $lastPullContent, int $pullTime, int $frequency): array
    {
        $shouldAddModifiedSince = false;
        if (isset($lastPullContent[AbstractCache::INDEX_EXP])) {
            $shouldAddModifiedSince = $this->shouldAddModifiedSince(
                $pullTime,
                (int) $lastPullContent[AbstractCache::INDEX_EXP],
                $frequency
            );
        }

        if ($shouldAddModifiedSince && isset($lastPullContent[AbstractCache::LAST_PULL])) {
            $headers['If-Modified-Since'] = $this->formatIfModifiedSinceHeader(
                (int) $lastPullContent[AbstractCache::LAST_PULL]
            );
        }

        return $headers;
    }

    private function handleListResponse(string $listResponse, array $blockDecision): array
    {
        $decisions = [];
        $listedIps = explode(\PHP_EOL, $listResponse);
        $this->logger->debug('Handle blocklist decisions', [
            'type' => 'CAPI_REM_HANDLE_LIST_DECISIONS',
            'list_count' => count($listedIps),
        ]);
        foreach ($listedIps as $listedIp) {
            $blockDecision['value'] = $listedIp;
            $decision = $this->convertRawDecision($blockDecision);
            if ($decision) {
                $decisions[] = $decision;
            }
        }

        return $decisions;
    }

    /**
     * This method allows to know if the "If-Modified-Since" should be added when pulling list decisions.
     *
     * @param int $pullTime           // Moment when the list is pulled
     * @param int $listExpirationTime // Expiration of the cached list decisions
     * @param int $frequency          // Amount of time in seconds that represents the average decision pull frequency
     */
    private function shouldAddModifiedSince(int $pullTime, int $listExpirationTime, int $frequency): bool
    {
        return ($listExpirationTime - $frequency) > $pullTime;
    }

    private function validateAllowlist(array $allowlist): bool
    {
        if (
            !empty($allowlist['id'])
            && !empty($allowlist['name'])
            && !empty($allowlist['description'])
            && !empty($allowlist['url'])
        ) {
            return true;
        }

        $this->logger->error('Retrieved allowlist is not as expected', [
            'type' => 'REM_RAW_DECISION_NOT_AS_EXPECTED',
            'raw_decision' => json_encode($allowlist),
        ]);

        return false;
    }

    private function validateBlocklist(array $blocklist): bool
    {
        if (
            !empty($blocklist['name'])
            && !empty($blocklist['url'])
            && !empty($blocklist['remediation'])
            && !empty($blocklist['scope'])
            && !empty($blocklist['duration'])
        ) {
            return true;
        }

        $this->logger->error('Retrieved blocklist is not as expected', [
            'type' => 'REM_RAW_DECISION_NOT_AS_EXPECTED',
            'raw_decision' => json_encode($blocklist),
        ]);

        return false;
    }
}
