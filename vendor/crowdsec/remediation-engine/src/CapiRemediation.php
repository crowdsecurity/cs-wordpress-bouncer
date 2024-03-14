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
        LoggerInterface $logger = null
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
    public function getIpRemediation(string $ip): string
    {
        $cachedDecisions = $this->getAllCachedDecisions($ip, $this->getCountryForIp($ip));

        if (!$cachedDecisions) {
            $this->logger->debug('There is no cached decision', [
                'type' => 'CAPI_REM_NO_CACHED_DECISIONS',
                'ip' => $ip,
            ]);

            $this->updateRemediationOriginCount(AbstractCache::CLEAN);

            // As CAPI is always in stream_mode, we do not store this bypass
            return Constants::REMEDIATION_BYPASS;
        }

        $remediationData = $this->handleRemediationFromDecisions($cachedDecisions);

        if (!empty($remediationData[self::INDEX_ORIGIN])) {
            $this->updateRemediationOriginCount((string) $remediationData[self::INDEX_ORIGIN]);
        }

        return $remediationData[self::INDEX_REM];
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
                $capiDecision['origin'] = Constants::ORIGIN_CAPI;
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

    private function handleListPullHeaders(array $headers, array $lastPullContent, int $pullTime): array
    {
        $shouldAddModifiedSince = false;
        if (isset($lastPullContent[AbstractCache::INDEX_EXP])) {
            $frequency = $this->getConfig('refresh_frequency_indicator') ?? Constants::REFRESH_FREQUENCY;
            $shouldAddModifiedSince = $this->shouldAddModifiedSince(
                $pullTime,
                (int) $lastPullContent[AbstractCache::INDEX_EXP],
                (int) $frequency
            );
        }

        if ($shouldAddModifiedSince && isset($lastPullContent[AbstractCache::LAST_PULL])) {
            $headers['If-Modified-Since'] = $this->formatIfModifiedSinceHeader(
                (int) $lastPullContent[AbstractCache::LAST_PULL]
            );
        }

        return $headers;
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
                    ];

                    $lastPullCacheKey = $this->getCacheStorage()->getCacheKey(
                        AbstractCache::LIST,
                        $listName
                    );

                    $lastPullItem = $this->getCacheStorage()->getItem($lastPullCacheKey);

                    $pullTime = time();
                    if ($lastPullItem->isHit()) {
                        $lastPullContent = $lastPullItem->get();
                        $headers = $this->handleListPullHeaders($headers, $lastPullContent, $pullTime);
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
            $this->logger->info('Something went wrong during list decisions process', [
                'type' => 'CAPI_REM_HANDLE_LIST_DECISIONS',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }

        return $decisions;
    }

    private function handleListResponse(string $listResponse, array $blockDecision): array
    {
        $decisions = [];
        $listedIps = explode(\PHP_EOL, $listResponse);
        $this->logger->debug('Handle list decisions', [
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

        $stored = $this->storeDecisions(array_merge($newDecisions, $listDecisions));
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
}
