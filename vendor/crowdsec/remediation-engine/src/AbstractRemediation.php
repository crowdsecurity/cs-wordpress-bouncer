<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine;

use CrowdSec\RemediationEngine\CacheStorage\AbstractCache;
use CrowdSec\RemediationEngine\CacheStorage\CacheStorageException;
use IPLib\Address\Type;
use IPLib\Factory;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;

abstract class AbstractRemediation
{
    /** @var string The CrowdSec name for blocklist */
    public const CS_BLOCK = 'blocklists';
    /** @var string The CrowdSec name for deleted decisions */
    public const CS_DEL = 'deleted';
    /** @var string The CrowdSec name for links */
    public const CS_LINK = 'links';
    /** @var string The CrowdSec name for new decisions */
    public const CS_NEW = 'new';
    /** @var string Origin index */
    public const INDEX_ORIGIN = 'origin';
    /** @var string Priority index */
    public const INDEX_PRIO = 'priority';
    /** @var string Remediation index */
    public const INDEX_REM = 'remediation';
    /**
     * @var AbstractCache
     */
    protected $cacheStorage;
    /**
     * @var array
     */
    protected $configs;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(array $configs, AbstractCache $cacheStorage, ?LoggerInterface $logger = null)
    {
        $this->configs = $configs;
        $this->cacheStorage = $cacheStorage;
        if (!$logger) {
            $logger = new Logger('null');
            $logger->pushHandler(new NullHandler());
        }
        $this->logger = $logger;
        $this->logger->debug('Instantiate remediation engine', [
            'type' => 'REM_INIT',
            'configs' => $configs,
            'cache' => \get_class($cacheStorage),
        ]);
    }

    /**
     * Clear cache.
     */
    public function clearCache(): bool
    {
        return $this->cacheStorage->clear();
    }

    public function getCacheStorage(): AbstractCache
    {
        return $this->cacheStorage;
    }

    /**
     * Retrieve a config by name.
     *
     * @return mixed|null
     */
    public function getConfig(string $name)
    {
        return (isset($this->configs[$name])) ? $this->configs[$name] : null;
    }

    /**
     * Retrieve remediation and its origin for a given IP.
     *
     * @returns array
     *              [
     *              'remediation' => (string): the remediation to apply (ex: 'ban', 'captcha', 'bypass'),
     *              'origin' => (string): the origin of the remediation (ex: 'CAPI', 'cscli')
     *             ]
     */
    abstract public function getIpRemediation(string $ip): array;

    /**
     * @throws InvalidArgumentException
     */
    public function getOriginsCount(): array
    {
        return $this->getOriginsCountItem();
    }

    /**
     * Prune cache.
     *
     * @throws CacheStorageException
     */
    public function pruneCache(): bool
    {
        return $this->cacheStorage->prune();
    }

    /**
     * Pull fresh decisions and update the cache.
     * Return the total of added and removed records. // ['new' => x, 'deleted' => y].
     */
    abstract public function refreshDecisions(): array;

    /**
     * Updating the "origins count" metrics in cache is the responsibility of the bouncer.
     * This method should be called by the bouncer after a remediation has been applied.
     *
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function updateMetricsOriginsCount(string $origin, string $remediation, int $delta = 1): int
    {
        $cacheOriginCount = $this->getOriginsCountItem();
        $count = isset($cacheOriginCount[$origin][$remediation]) ?
            (int) $cacheOriginCount[$origin][$remediation] :
            0;

        $count += $delta;
        $finalCount = max(0, $count);
        $this->cacheStorage->upsertItem(
            AbstractCache::ORIGINS_COUNT,
            [
                $origin => [
                    $remediation => $finalCount,
                ],
            ],
            0,
            [AbstractCache::ORIGINS_COUNT]
        );

        return $finalCount;
    }

    protected function convertRawDecision(array $rawDecision): ?Decision
    {
        if (!$this->validateRawDecision($rawDecision)) {
            return null;
        }
        // The existence of the following indexes must be guaranteed by the validateRawDecision method
        $value = $rawDecision['value'];
        $type = $this->normalize($rawDecision['type']);
        $origin = $this->handleDecisionOrigin($rawDecision);
        $duration = $rawDecision['duration'];
        $scope = $this->normalize($rawDecision['scope']);

        return new Decision(
            $this->handleDecisionIdentifier($origin, $type, $scope, $value),
            $scope,
            $value,
            $type,
            $origin,
            $this->handleDecisionExpiresAt($type, $duration)
        );
    }

    protected function convertRawDecisionsToDecisions(array $rawDecisions): array
    {
        $decisions = [];
        foreach ($rawDecisions as $rawDecision) {
            $decision = $this->convertRawDecision($rawDecision);
            if ($decision) {
                $decisions[] = $decision;
            }
        }

        return $decisions;
    }

    /**
     * @throws InvalidArgumentException|CacheStorageException
     */
    protected function getAllCachedDecisions(string $ip, string $country): array
    {
        // Ask cache for Ip scoped decision
        $ipDecisions = $this->cacheStorage->retrieveDecisionsForIp(Constants::SCOPE_IP, $ip);
        // Ask cache for Range scoped decision
        $rangeDecisions = Type::T_IPv4 === $this->getIpType($ip)
            ? $this->cacheStorage->retrieveDecisionsForIp(Constants::SCOPE_RANGE, $ip)
            : [];
        // Ask cache for Country scoped decision
        $countryDecisions = $country ? $this->cacheStorage->retrieveDecisionsForCountry($country) : [];

        return array_merge(
            !empty($ipDecisions[AbstractCache::STORED]) ? $ipDecisions[AbstractCache::STORED] : [],
            !empty($rangeDecisions[AbstractCache::STORED]) ? $rangeDecisions[AbstractCache::STORED] : [],
            !empty($countryDecisions[AbstractCache::STORED]) ? $countryDecisions[AbstractCache::STORED] : []
        );
    }

    /**
     * @throws CacheException
     * @throws CacheStorageException
     * @throws InvalidArgumentException
     * @throws RemediationException
     */
    protected function getCountryForIp(string $ip): string
    {
        $geolocConfigs = $this->getConfig('geolocation');
        if (!empty($geolocConfigs['enabled'])) {
            $geolocation = new Geolocation($geolocConfigs, $this->cacheStorage, $this->logger);
            $countryResult = $geolocation->handleCountryResultForIp($ip);

            return !empty($countryResult['country']) ? $countryResult['country'] : '';
        }

        return '';
    }

    protected function getIpType(string $ip): int
    {
        $address = Factory::parseAddressString($ip);

        return !is_null($address) ? $address->getAddressType() : 0;
    }

    /**
     * Converts durations like 3h24m59.5565s, 3h24m5957ms, 149h, etc. in seconds.
     */
    protected function parseDurationToSeconds(string $duration): int
    {
        $re = '/(-)?(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)(\.\d+)?s)?(?:(\d+)ms)?/';
        preg_match($re, $duration, $matches);

        if (empty($matches[0])) {
            $this->logger->error('An error occurred during duration parsing', [
                'type' => 'REM_DECISION_DURATION_PARSE_ERROR',
                'duration' => $duration,
            ]);

            return 0;
        }

        $seconds = 0;
        // Parse hours
        if (!empty($matches[2])) {
            $seconds += ((int) $matches[2]) * 3600;
        }

        // Parse minutes
        if (!empty($matches[3])) {
            $seconds += ((int) $matches[3]) * 60;
        }

        // Parse seconds
        if (!empty($matches[4])) {
            $seconds += (int) $matches[4];
        }

        // Parse fractional seconds
        if (!empty($matches[5])) {
            $seconds += (float) $matches[5];
        }

        // Parse milliseconds
        if (!empty($matches[6])) {
            $seconds += ((int) $matches[6]) * 0.001;
        }

        // Handle negative durations
        if ('-' === $matches[1]) {
            $seconds *= -1;
        }

        return (int) round($seconds);
    }

    /**
     * Retrieve only the remediation with the highest priority from decisions.
     *
     * It will remove expired decisions.
     * It will use fallback for unknown remediation.
     * It will cap the remediation level if needed.
     */
    protected function processCachedDecisions(array $cacheDecisions): array
    {
        $remediationData = $this->retrieveRemediationFromCachedDecisions($cacheDecisions);
        $origin = !empty($remediationData[self::INDEX_ORIGIN]) ? (string) $remediationData[self::INDEX_ORIGIN] : '';
        $remediation = !empty($remediationData[self::INDEX_REM]) ? (string) $remediationData[self::INDEX_REM] :
            Constants::REMEDIATION_BYPASS;

        return [
            Constants::REMEDIATION_KEY => $remediation,
            Constants::ORIGIN_KEY => $origin,
        ];
    }

    /**
     * Remove decisions from cache.
     *
     * @throws CacheStorageException
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    protected function removeDecisions(array $decisions): array
    {
        if (!$decisions) {
            return [AbstractCache::DONE => 0, AbstractCache::REMOVED => []];
        }
        $deferCount = 0;
        $doneCount = 0;
        $removed = [];
        foreach ($decisions as $decision) {
            $removeResult = $this->cacheStorage->removeDecision($decision);
            $deferCount += $removeResult[AbstractCache::DEFER];
            $doneCount += $removeResult[AbstractCache::DONE];
            if (!empty($removeResult[AbstractCache::REMOVED])) {
                $removed[] = $removeResult[AbstractCache::REMOVED];
            }
        }

        return [
            AbstractCache::DONE => $doneCount + ($this->cacheStorage->commit() ? $deferCount : 0),
            AbstractCache::REMOVED => $removed,
        ];
    }

    /**
     * Add decisions in cache.
     * If decisions are already in cache, result will be [AbstractCache::DONE => 0, AbstractCache::STORED => []].
     *
     * @return array
     *               [
     *               AbstractCache::DONE => (int): number of stored decisions by the current call,
     *               AbstractCache::STORED => (array): cached decisions stored by the current call
     *               ]
     *
     * @throws CacheStorageException
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    protected function storeDecisions(array $decisions): array
    {
        if (!$decisions) {
            return [AbstractCache::DONE => 0, AbstractCache::STORED => []];
        }
        $deferCount = 0;
        $doneCount = 0;
        $stored = [];
        foreach ($decisions as $decision) {
            $storeResult = $this->cacheStorage->storeDecision($decision);
            $deferCount += $storeResult[AbstractCache::DEFER];
            $doneCount += $storeResult[AbstractCache::DONE];
            if (!empty($storeResult[AbstractCache::STORED])) {
                $stored[] = $storeResult[AbstractCache::STORED];
            }
        }

        return [
            AbstractCache::DONE => $doneCount + ($this->cacheStorage->commit() ? $deferCount : 0),
            AbstractCache::STORED => $stored,
        ];
    }

    /**
     * Cap the remediation to a fixed value given by the bouncing level configuration.
     *
     * @param string $remediation (ex: 'ban', 'captcha', 'bypass')
     *
     * @return string $remediation The resulting remediation to use (ex: 'ban', 'captcha', 'bypass')
     */
    private function capRemediationLevel(string $remediation): string
    {
        if (Constants::REMEDIATION_BYPASS === $remediation) {
            return Constants::REMEDIATION_BYPASS;
        }

        $orderedRemediations = (array) $this->getConfig('ordered_remediations');

        $bouncingLevel = $this->getConfig('bouncing_level') ?? Constants::BOUNCING_LEVEL_NORMAL;
        // Compute max remediation level
        switch ($bouncingLevel) {
            case Constants::BOUNCING_LEVEL_DISABLED:
                $maxRemediationLevel = Constants::REMEDIATION_BYPASS;
                break;
            case Constants::BOUNCING_LEVEL_FLEX:
                $maxRemediationLevel = Constants::REMEDIATION_CAPTCHA;
                break;
            case Constants::BOUNCING_LEVEL_NORMAL:
            default:
                $maxRemediationLevel = Constants::REMEDIATION_BAN;
                break;
        }

        $currentIndex = (int) array_search($remediation, $orderedRemediations);
        $maxIndex = (int) array_search(
            $maxRemediationLevel,
            $orderedRemediations
        );
        $finalRemediation = $remediation;
        if ($currentIndex < $maxIndex) {
            $finalRemediation = $orderedRemediations[$maxIndex];
            $this->logger->debug('Original remediation has been capped', [
                'origin' => $remediation,
                'final' => $finalRemediation,
            ]);
        }
        $this->logger->info('Final remediation', [
            'remediation' => $finalRemediation,
        ]);

        return $finalRemediation;
    }

    /**
     * Compare two priorities.
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private static function comparePriorities(array $a, array $b): int
    {
        $a = $a[self::INDEX_PRIO];
        $b = $b[self::INDEX_PRIO];
        if ($a == $b) {
            return 0;
        }

        return ($a < $b) ? -1 : 1;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getOriginsCountItem(): array
    {
        $originsCountItem = $this->cacheStorage->getItem(AbstractCache::ORIGINS_COUNT);

        return $originsCountItem->isHit() ? (array) $originsCountItem->get() : [];
    }

    private function handleDecisionExpiresAt(string $type, string $duration): int
    {
        $duration = $this->parseDurationToSeconds($duration);
        if (Constants::REMEDIATION_BYPASS !== $type && !$this->getConfig('stream_mode')) {
            $duration = min((int) $this->getConfig('bad_ip_cache_duration'), $duration);
        }

        return time() + $duration;
    }

    private function handleDecisionIdentifier(
        string $origin,
        string $type,
        string $scope,
        string $value
    ): string {
        return
            $this->normalize($origin) . Decision::ID_SEP .
            $type . Decision::ID_SEP .
            $scope . Decision::ID_SEP .
            $value;
    }

    private function handleDecisionOrigin(array $rawDecision): string
    {
        $origin = $rawDecision['origin'];
        if (Constants::ORIGIN_LISTS === $origin) {
            // The existence of the $rawDecision['scenario'] must be guaranteed by the validateRawDecision method
            $origin .= Constants::ORIGIN_LISTS_SEPARATOR . $rawDecision['scenario'];
        }

        return $origin;
    }

    private function normalize(string $value): string
    {
        return strtolower($value);
    }

    private function retrieveRemediationFromCachedDecisions(array $cacheDecisions): array
    {
        $cleanDecisions = $this->cacheStorage->cleanCachedValues($cacheDecisions);
        $sortedDecisions = $this->sortDecisionsByPriority($cleanDecisions);
        $this->logger->debug('Decisions have been sorted by priority', [
            'type' => 'REM_SORTED_DECISIONS',
            'decisions' => $sortedDecisions,
        ]);
        // Keep only a remediation with the highest priority
        $highestRemediation = $sortedDecisions[0][AbstractCache::INDEX_MAIN] ?? Constants::REMEDIATION_BYPASS;
        $origin = $sortedDecisions[0][AbstractCache::INDEX_ORIGIN] ?? '';
        // Cap the remediation level
        $cappedRemediation = $this->capRemediationLevel($highestRemediation);

        return [
            self::INDEX_REM => $cappedRemediation,
            self::INDEX_ORIGIN => Constants::REMEDIATION_BYPASS === $cappedRemediation ? AbstractCache::CLEAN : $origin,
        ];
    }

    /**
     * Sort the decision array of a cache item, by remediation priorities, using fallback if needed.
     */
    private function sortDecisionsByPriority(array $decisions): array
    {
        if (!$decisions) {
            return $decisions;
        }
        // Add priorities
        $orderedRemediations = (array) $this->getConfig('ordered_remediations');
        $fallback = $this->getConfig('fallback_remediation');
        $decisionsWithPriority = [];
        foreach ($decisions as $decision) {
            $priority = array_search($decision[AbstractCache::INDEX_MAIN], $orderedRemediations);
            // Use fallback for unknown remediation
            if (false === $priority) {
                $this->logger->debug('Fallback used to handle unknown remediation', [
                    'unknown_remediation' => $decision[AbstractCache::INDEX_MAIN],
                    'fallback' => $fallback,
                ]);
                $priority = array_search($fallback, $orderedRemediations);
                $decision[AbstractCache::INDEX_MAIN] = $fallback;
            }
            $decision[self::INDEX_PRIO] = $priority;
            $decisionsWithPriority[] = $decision;
        }
        // Sort by priorities.
        /** @var callable $compareFunction */
        $compareFunction = self::class . '::comparePriorities';
        usort($decisionsWithPriority, $compareFunction);

        return $decisionsWithPriority;
    }

    private function validateRawDecision(array $rawDecision): bool
    {
        $result = false;

        if (
            !empty($rawDecision['scope'])
            && !empty($rawDecision['value'])
            && !empty($rawDecision['type'])
            && !empty($rawDecision['origin'])
            && !empty($rawDecision['duration'])
        ) {
            $result = true;
            // We don't want blocklists decisions without a scenario
            if (
                Constants::ORIGIN_LISTS === $rawDecision['origin']
                && empty($rawDecision['scenario'])
            ) {
                $result = false;
            }
        }

        if (false === $result) {
            $this->logger->error('Retrieved raw decision is not as expected', [
                'type' => 'REM_RAW_DECISION_NOT_AS_EXPECTED',
                'raw_decision' => json_encode($rawDecision),
            ]);
        }

        return $result;
    }
}
