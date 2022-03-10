<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

use DateTime;
use IPLib\Address\AddressInterface;
use IPLib\Address\Type;
use IPLib\Factory;
use IPLib\Range\Subnet;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\PruneableInterface;

/**
 * The cache mecanism to store every decisions from LAPI/CAPI. Symfony Cache component powered.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class ApiCache
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ApiClient */
    private $apiClient;

    /** @var AbstractAdapter */
    private $adapter;

    /** @var bool */
    private $liveMode = null;

    /** @var int */
    private $cacheExpirationForCleanIp = null;

    /** @var int */
    private $cacheExpirationForBadIp = null;

    /** @var bool */
    private $warmedUp = null;

    /**
     * @param LoggerInterface $logger    The logger to use
     * @param ApiClient       $apiClient The APIClient instance to use
     * @param AbstractAdapter $adapter   The AbstractAdapter adapter to use
     */
    public function __construct(LoggerInterface $logger, ApiClient $apiClient = null, AbstractAdapter $adapter = null)
    {
        $this->logger = $logger;
        $this->apiClient = $apiClient ?: new ApiClient($logger);
        $this->adapter = $adapter ?: new FilesystemAdapter();
    }

    /**
     * Configure this instance.
     *
     * @param bool   $liveMode                  If we use the live mode (else we use the stream mode)
     * @param string $apiUrl                    The URL of the LAPI
     * @param int    $timeout                   The timeout well calling LAPI
     * @param string $userAgent                 The user agent to use when calling LAPI
     * @param string $apiKey                    The Bouncer API Key to use to connect LAPI
     * @param int    $cacheExpirationForCleanIp The duration to cache an IP considered as clean by LAPI
     * @param int    $cacheExpirationForBadIp   The duration to cache an IP considered as bad by LAPI
     * @param string $fallbackRemediation       The remediation to use when the remediation sent by LAPI is not supported by this library
     */
    public function configure(
        bool $liveMode,
        string $apiUrl,
        int $timeout,
        string $userAgent,
        string $apiKey,
        int $cacheExpirationForCleanIp,
        int $cacheExpirationForBadIp,
        string $fallbackRemediation
    ): void {
        $this->liveMode = $liveMode;
        $this->cacheExpirationForCleanIp = $cacheExpirationForCleanIp;
        $this->cacheExpirationForBadIp = $cacheExpirationForBadIp;
        $this->fallbackRemediation = $fallbackRemediation;
        $cacheConfigItem = $this->adapter->getItem('cacheConfig');
        $cacheConfig = $cacheConfigItem->get();
        $this->warmedUp = (\is_array($cacheConfig) && isset($cacheConfig['warmed_up'])
            && true === $cacheConfig['warmed_up']);
        $this->logger->debug('', [
            'type' => 'API_CACHE_INIT',
            'adapter' => \get_class($this->adapter),
            'mode' => ($liveMode ? 'live' : 'stream'),
            'exp_clean_ips' => $cacheExpirationForCleanIp,
            'exp_bad_ips' => $cacheExpirationForBadIp,
            'warmed_up' => ($this->warmedUp ? 'true' : 'false'),
        ]);
        $this->apiClient->configure($apiUrl, $timeout, $userAgent, $apiKey);
    }

    /**
     * Add remediation to a Symfony Cache Item identified by IP.
     */
    private function addRemediationToCacheItem(string $ip, string $type, int $expiration, int $decisionId): string
    {
        $item = $this->adapter->getItem(base64_encode($ip));

        // Merge with existing remediations (if any).
        $remediations = $item->isHit() ? $item->get() : [];

        $index = array_search(Constants::REMEDIATION_BYPASS, array_column($remediations, 0));
        if (false !== $index) {
            $this->logger->debug('', [
                'type' => 'IP_CLEAN_TO_BAD',
                'ip' => $ip,
                'old_remediation' => Constants::REMEDIATION_BYPASS,
            ]);
            unset($remediations[$index]);
        }

        $remediations[] = [
            $type,
            $expiration,
            $decisionId,
        ]; // erase previous decision with the same id

        // Build the item lifetime in cache and sort remediations by priority
        $maxLifetime = max(array_column($remediations, 1));
        $prioritizedRemediations = Remediation::sortRemediationByPriority($remediations);

        $item->set($prioritizedRemediations);
        $item->expiresAt(new DateTime('@'.$maxLifetime));

        // Save the cache without committing it to the cache system.
        // Useful to improve performance when updating the cache.
        if (!$this->adapter->saveDeferred($item)) {
            throw new BouncerException("cache#$ip: Unable to save this deferred item in cache: "."$type for $expiration sec, (decision $decisionId)");
        }

        return $prioritizedRemediations[0][0];
    }

    /**
     * Remove a decision from a Symfony Cache Item identified by ip.
     */
    private function removeDecisionFromRemediationItem(string $ip, int $decisionId): bool
    {
        $item = $this->adapter->getItem(base64_encode($ip));
        $remediations = $item->get();

        $index = false;
        if ($remediations) {
            $index = array_search($decisionId, array_column($remediations, 2));
        }

        // If decision was not found for this cache item early return.
        if (false === $index) {
            return false;
        }
        unset($remediations[$index]);

        if (!$remediations) {
            $this->logger->debug('', [
                'type' => 'CACHE_ITEM_REMOVED',
                'ip' => $ip,
            ]);
            $this->adapter->delete(base64_encode($ip));

            return true;
        }
        // Build the item lifetime in cache and sort remediations by priority
        $maxLifetime = max(array_column($remediations, 1));
        $cacheContent = Remediation::sortRemediationByPriority($remediations);
        $item->expiresAt(new DateTime('@'.$maxLifetime));
        $item->set($cacheContent);

        // Save the cache without commiting it to the cache system.
        // Useful to improve performance when updating the cache.
        if (!$this->adapter->saveDeferred($item)) {
            throw new BouncerException("cache#$ip: Unable to save item");
        }
        $this->logger->debug('', [
            'type' => 'DECISION_REMOVED',
            'decision' => $decisionId,
            'ips' => [$ip],
        ]);

        return true;
    }

    /**
     * Parse "duration" entries returned from API to a number of seconds.
     */
    private static function parseDurationToSeconds(string $duration): int
    {
        $re = '/(-?)(?:(?:(\d+)h)?(\d+)m)?(\d+).\d+(m?)s/m';
        preg_match($re, $duration, $matches);
        if (!\count($matches)) {
            throw new BouncerException("Unable to parse the following duration: ${$duration}.");
        }
        $seconds = 0;
        if (isset($matches[2])) {
            $seconds += ((int) $matches[2]) * 3600; // hours
        }
        if (isset($matches[3])) {
            $seconds += ((int) $matches[3]) * 60; // minutes
        }
        if (isset($matches[4])) {
            $seconds += ((int) $matches[4]); // seconds
        }
        if ('m' === ($matches[5])) { // units in milliseconds
            $seconds *= 0.001;
        }
        if ('-' === ($matches[1])) { // negative
            $seconds *= -1;
        }

        return (int) round($seconds);
    }

    /**
     * Format a remediation item of a cache item.
     * This format use a minimal amount of data allowing less cache data consumption.
     */
    private function formatRemediationFromDecision(?array $decision): array
    {
        if (!$decision) {
            $duration = time() + $this->cacheExpirationForCleanIp;
            if (!$this->liveMode) {
                // In stream mode we considere an clean IP forever... until the next resync.
                $duration = 315360000; // in this case, forever is 10 years as PHP_INT_MAX will cause trouble with the Memcached Adapter (int to float unwanted conversion)
            }

            return [Constants::REMEDIATION_BYPASS, $duration, 0];
        }

        $duration = self::parseDurationToSeconds($decision['duration']);

        // Don't set a max duration in stream mode to avoid bugs. Only the stream update has to change the cache state.
        if ($this->liveMode) {
            $duration = min($this->cacheExpirationForBadIp, $duration);
        }

        return [
            $decision['type'], // ex: ban, captcha
            time() + $duration, // expiration timestamp
            $decision['id'],
        ];
    }

    private function defferUpdateCacheConfig(array $config): void
    {
        $cacheConfigItem = $this->adapter->getItem('cacheConfig');
        $cacheConfig = $cacheConfigItem->isHit() ? $cacheConfigItem->get() : [];
        $cacheConfig = array_replace_recursive($cacheConfig, $config);
        $cacheConfigItem->set($cacheConfig);
        $this->adapter->saveDeferred($cacheConfigItem);
    }

    /**
     * Update the cached remediation of the specified IP from these new decisions.
     */
    private function saveRemediationsForIp(array $decisions, string $ip): string
    {
        $remediationResult = Constants::REMEDIATION_BYPASS;
        if (\count($decisions)) {
            foreach ($decisions as $decision) {
                if (!\in_array($decision['type'], Constants::ORDERED_REMEDIATIONS)) {
                    $this->logger->warning('', ['type' => 'UNKNOWN_REMEDIATION', 'unknown' => $decision['type'], 'fallback' => $this->fallbackRemediation]);
                    $decision['type'] = $this->fallbackRemediation;
                }
                $remediation = $this->formatRemediationFromDecision($decision);
                $type = $remediation[0];
                $exp = $remediation[1];
                $id = $remediation[2];
                $remediationResult = $this->addRemediationToCacheItem($ip, $type, $exp, $id);
            }
        } else {
            $remediation = $this->formatRemediationFromDecision(null);
            $type = $remediation[0];
            $exp = $remediation[1];
            $id = $remediation[2];
            $remediationResult = $this->addRemediationToCacheItem($ip, $type, $exp, $id);
        }
        $this->commit();

        return $remediationResult;
    }

    /**
     * Update the cached remediations from these new decisions.
     */
    private function saveRemediations(array $decisions): array
    {
        $errors = [];
        foreach ($decisions as $decision) {
            $remediation = $this->formatRemediationFromDecision($decision);
            $type = $remediation[0];
            if (!\in_array($remediation[0], Constants::ORDERED_REMEDIATIONS)) {
                $this->logger->warning('', ['type' => 'UNKNOWN_REMEDIATION', 'unknown' => $remediation[0], 'fallback' => $this->fallbackRemediation]);
                $remediation[0] = $this->fallbackRemediation;
            }
            $exp = $remediation[1];
            $id = $remediation[2];

            if ('Ip' === $decision['scope']) {
                $address = Factory::addressFromString($decision['value']);
                if (null === $address) {
                    $this->logger->warning('', [
                        'type' => 'INVALID_IP_TO_ADD_FROM_REMEDIATION',
                        'decision' => $decision,
                    ]);
                    continue;
                }
                $this->addRemediationToCacheItem($address->toString(), $type, $exp, $id);
            } elseif ('Range' === $decision['scope']) {
                $range = Subnet::fromString($decision['value']);

                $addressType = $range->getAddressType();
                $isIpv6 = (Type::T_IPv6 === $addressType);
                if ($isIpv6 || ($range->getNetworkPrefix() < 24)) {
                    $error = ['type' => 'DECISION_RANGE_TO_ADD_IS_TOO_LARGE', 'decision' => $decision['id'], 'range' => $decision['value'], 'remediation' => $type, 'expiration' => $exp];
                    $errors[] = $error;
                    $this->logger->warning('', $error);
                    continue;
                }
                $comparableEndAddress = $range->getEndAddress()->getComparableString();

                $comparableEndAddress = $range->getComparableEndString();
                $address = $range->getStartAddress();
                $this->addRemediationToCacheItem($address->toString(), $type, $exp, $id);
                $ipCount = 1;
                do {
                    $address = $address->getNextAddress();
                    $this->addRemediationToCacheItem($address->toString(), $type, $exp, $id);
                    ++$ipCount;
                    if ($ipCount >= 1000) {
                        throw new BouncerException("Unable to store the decision ${$decision['id']}, the IP range: ${$decision['value']} is too large and can cause storage problem. Decision ignored.");
                    }
                } while (0 !== strcmp($address->getComparableString(), $comparableEndAddress));
            }
        }

        return ['success' => $this->commit(), 'errors' => $errors];
    }

    private function removeRemediations(array $decisions): array
    {
        $errors = [];
        $count = 0;
        foreach ($decisions as $decision) {
            if ('Ip' === $decision['scope']) {
                $address = Factory::addressFromString($decision['value']);
                if (null === $address) {
                    $this->logger->warning('', [
                        'type' => 'INVALID_IP_TO_REMOVE_FROM_REMEDIATION',
                        'decision' => $decision,
                    ]);
                    continue;
                }
                if (!$this->removeDecisionFromRemediationItem($address->toString(), $decision['id'])) {
                    $this->logger->debug('', ['type' => 'DECISION_TO_REMOVE_NOT_FOUND_IN_CACHE', 'decision' => $decision['id']]);
                } else {
                    $this->logger->debug('', [
                    'type' => 'DECISION_REMOVED',
                    'decision' => $decision['id'],
                    'value' => $decision['value'],
                    ]);
                }
            } elseif ('Range' === $decision['scope']) {
                $range = Subnet::fromString($decision['value']);

                $addressType = $range->getAddressType();
                $isIpv6 = (Type::T_IPv6 === $addressType);
                if ($isIpv6 || ($range->getNetworkPrefix() < 24)) {
                    $error = ['type' => 'DECISION_RANGE_TO_REMOVE_IS_TOO_LARGE', 'decision' => $decision['id'], 'range' => $decision['value']];
                    $errors[] = $error;
                    $this->logger->warning('', $error);
                    continue;
                }

                $comparableEndAddress = $range->getComparableEndString();
                $address = $range->getStartAddress();
                if (!$this->removeDecisionFromRemediationItem($address->toString(), $decision['id'])) {
                    $this->logger->debug('', ['type' => 'DECISION_TO_REMOVE_NOT_FOUND_IN_CACHE', 'decision' => $decision['id']]);
                }
                $ipCount = 1;
                $success = true;
                do {
                    $address = $address->getNextAddress();
                    if (!$this->removeDecisionFromRemediationItem($address->toString(), $decision['id'])) {
                        $success = false;
                    }
                    ++$ipCount;
                    if ($ipCount >= 1000) {
                        throw new BouncerException("Unable to store the decision ${$decision['id']}, the IP range: ${$decision['value']} is too large and can cause storage problem. Decision ignored.");
                    }
                } while (0 !== strcmp($address->getComparableString(), $comparableEndAddress));

                if ($success) {
                    $this->logger->debug('', [
                        'type' => 'DECISION_REMOVED',
                        'decision' => $decision['id'],
                        'value' => $decision['value'],
                        ]);
                    ++$count;
                } else {
                    // The API may return stale deletion events due to API design.
                    // Ignoring them is therefore not a problem.
                    $this->logger->debug('', ['type' => 'DECISION_TO_REMOVE_NOT_FOUND_IN_CACHE', 'decision' => $decision['id']]);
                }
            }
        }

        $this->commit();

        return ['count' => $count, 'errors' => $errors];
    }

    public function clear(): bool
    {
        $this->setCustomErrorHandler();
        try {
            $cleared = $this->adapter->clear();
        } finally {
            $this->unsetCustomErrorHandler();
        }
        $this->warmedUp = false;
        $this->defferUpdateCacheConfig(['warmed_up' => $this->warmedUp]);
        $this->commit();
        $this->logger->info('', ['type' => 'CACHE_CLEARED']);

        return $cleared;
    }

    /**
     * Used in stream mode only.
     * Warm the cache up.
     * Used when the stream mode has just been activated.
     *
     * @return array "count": number of decisions added, "errors": decisions not added
     */
    public function warmUp(): array
    {
        $addErrors = [];

        if ($this->warmedUp) {
            $this->clear();
        }
        $this->logger->debug('', ['type' => 'START_CACHE_WARMUP']);
        $startup = true;
        $decisionsDiff = $this->apiClient->getStreamedDecisions($startup);
        $newDecisions = $decisionsDiff['new'];

        $nbNew = 0;
        if ($newDecisions) {
            $saveResult = $this->saveRemediations($newDecisions);
            $addErrors = $saveResult['errors'];
            $this->warmedUp = $saveResult['success'];
            $this->defferUpdateCacheConfig(['warmed_up' => $this->warmedUp]);
            $this->commit();
            if (!$this->warmedUp) {
                throw new BouncerException('Unable to warm the cache up');
            }
            $nbNew = \count($newDecisions);
        }

        // Store the fact that the cache has been warmed up.
        $this->defferUpdateCacheConfig(['warmed_up' => true]);

        $this->commit();
        $this->logger->info('', ['type' => 'CACHE_WARMED_UP', 'added_decisions' => $nbNew]);

        return ['count' => $nbNew, 'errors' => $addErrors];
    }

    /**
     * Used in stream mode only.
     * Pull decisions updates from the API and update the cached remediations.
     * Used for the stream mode when we have to update the remediations list.
     *
     * @return array number of deleted and new decisions, and errors when processing decisions
     */
    public function pullUpdates(): array
    {
        $deletionErrors = [];
        $addErrors = [];
        if (!$this->warmedUp) {
            $warmUpResult = $this->warmUp();
            $addErrors = $warmUpResult['errors'];

            return ['deleted' => 0, 'new' => $warmUpResult['count'], 'deletionErrors' => $deletionErrors, 'addErrors' => $addErrors];
        }

        $this->logger->debug('', ['type' => 'START_CACHE_UPDATE']);
        $decisionsDiff = $this->apiClient->getStreamedDecisions();
        $newDecisions = $decisionsDiff['new'];
        $deletedDecisions = $decisionsDiff['deleted'];

        $nbDeleted = 0;
        if ($deletedDecisions) {
            $removingResult = $this->removeRemediations($deletedDecisions);
            $deletionErrors = $removingResult['errors'];
            $nbDeleted = $removingResult['count'];
        }

        $nbNew = 0;
        if ($newDecisions) {
            $saveResult = $this->saveRemediations($newDecisions);
            $addErrors = $saveResult['errors'];
            $nbNew = \count($newDecisions);
        }

        $this->logger->debug('', ['type' => 'CACHE_UPDATED', 'deleted' => $nbDeleted, 'new' => $nbNew]);

        return ['deleted' => $nbDeleted, 'new' => $nbNew, 'deletionErrors' => $deletionErrors, 'addErrors' => $addErrors];
    }

    /**
     * This method is called when nothing has been found in cache for the requested IP.
     * In live mode is enabled, calls the API for decisions concerning the specified IP
     * In stream mode, as we considere cache is the single source of truth, the IP is considered clean.
     * Finally the result is stored in caches for further calls.
     */
    private function miss(string $ipToQuery, string $cacheKey): string
    {
        $decisions = [];

        if ($this->liveMode) {
            $this->logger->debug('', ['type' => 'DIRECT_API_CALL', 'ip' => $ipToQuery]);
            $decisions = $this->apiClient->getFilteredDecisions(['ip' => $ipToQuery]);
        }

        return $this->saveRemediationsForIp($decisions, $cacheKey);
    }

    /**
     * Used in both mode (stream and ruptue).
     * This method formats the cached item as a remediation.
     * It returns the highest remediation level found.
     */
    private function hit(string $ip): string
    {
        $remediations = $this->adapter->getItem(base64_encode($ip))->get();

        // We apply array values first because keys are ids.
        $firstRemediation = array_values($remediations)[0];

        /** @var string */
        return $firstRemediation[0];
    }

    /**
     * Request the cache for the specified IP.
     *
     * @return string the computed remediation string, or null if no decision was found
     */
    public function get(AddressInterface $address): string
    {
        $cacheKey = $address->toString();
        $this->logger->debug('', ['type' => 'START_IP_CHECK', 'ip' => $cacheKey]);
        if (!$this->liveMode && !$this->warmedUp) {
            throw new BouncerException('CrowdSec Bouncer configured in "stream" mode. Please warm the cache up before trying to access it.');
        }

        if ($this->adapter->hasItem(base64_encode($cacheKey))) {
            $remediation = $this->hit($cacheKey);
            $cache = 'hit';
        } else {
            $remediation = $this->miss($address->toString(), $cacheKey);
            $cache = 'miss';
        }

        if (Constants::REMEDIATION_BYPASS === $remediation) {
            $this->logger->info('', ['type' => 'CLEAN_IP', 'ip' => $cacheKey, 'cache' => $cache]);
        } else {
            $this->logger->warning('', [
                'type' => 'BAD_IP',
                'ip' => $cacheKey,
                'remediation' => $remediation,
                'cache' => $cache,
            ]);
        }

        return $remediation;
    }

    /**
     * Prune the cache (only when using PHP File System cache).
     */
    public function prune(): bool
    {
        if ($this->adapter instanceof PruneableInterface) {
            $pruned = $this->adapter->prune();
            $this->logger->debug('', ['type' => 'CACHE_PRUNED']);

            return $pruned;
        }

        throw new BouncerException('Cache Adapter'.\get_class($this->adapter).' is not prunable.');
    }

    /**
     * When Memcached connection fail, it throw an unhandled warning.
     * To catch this warning as a clean execption we have to temporarily change the error handler.
     */
    private function setCustomErrorHandler(): void
    {
        if ($this->adapter instanceof MemcachedAdapter) {
            set_error_handler(function ($errno, $errmsg) {
                throw new BouncerException('Error when connecting to Memcached. Please fix the Memcached DSN or select another cache technology. Original message was: '.$errmsg);
            });
        }
    }

    /**
     * When the selected cache adapter is MemcachedAdapter, revert to the previous error handler.
     * */
    private function unsetCustomErrorHandler(): void
    {
        if ($this->adapter instanceof MemcachedAdapter) {
            restore_error_handler();
        }
    }

    /**
     * Wrap the cacheAdapter to catch warnings.
     *
     * @throws BouncerException if the connection was not successful
     * */
    private function commit(): bool
    {
        $this->setCustomErrorHandler();
        try {
            $result = $this->adapter->commit();
        } finally {
            $this->unsetCustomErrorHandler();
        }

        return $result;
    }

    /**
     * Test the connection to the cache system (Redis or Memcached).
     *
     * @throws BouncerException if the connection was not successful
     * */
    public function testConnection(): void
    {
        $this->setCustomErrorHandler();
        try {
            $this->adapter->getItem(' ');
        } finally {
            $this->unsetCustomErrorHandler();
        }
    }
}
