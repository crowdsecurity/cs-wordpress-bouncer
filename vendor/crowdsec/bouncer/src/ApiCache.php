<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

use DateTime;
use Exception;
use IPLib\Address\AddressInterface;
use IPLib\Address\Type;
use IPLib\Factory;
use IPLib\Range\Subnet;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\PruneableInterface;

/**
 * The cache mechanism to store every decision from LAPI/CAPI. Symfony Cache component powered.
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
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ApiClient
     */
    private $apiClient;

    /** @var TagAwareAdapterInterface */
    private $adapter;

    /**
     * @var Geolocation
     */
    private $geolocation;

    /** @var bool */
    private $streamMode = false;

    /**
     * @var int
     */
    private $cacheExpirationForCleanIp = 0;

    /**
     * @var int
     */
    private $cacheExpirationForBadIp = 0;

    /**
     * @var int
     */
    private $cacheExpirationForCaptcha = 0;

    /**
     * @var int
     */
    private $cacheExpirationForGeo = 0;

    /** @var bool */
    private $warmedUp = false;

    /**
     * @var string
     */
    private $fallbackRemediation;

    /**
     * @var array|null
     */
    private $geolocConfig;

    /**
     * @var array
     */
    private $cacheKeys = [];

    /**
     * @var array|null
     */
    private $scopes;

    public const CACHE_SEP = '_';

    /**
     * @param LoggerInterface      $logger      The logger to use
     * @param ApiClient|null       $apiClient   The APIClient instance to use
     * @param AbstractAdapter|null $adapter     The AbstractAdapter adapter to use
     * @param Geolocation|null     $geolocation The Geolocation helper to use
     */
    public function __construct(LoggerInterface $logger, ApiClient $apiClient = null, TagAwareAdapterInterface $adapter =
    null, Geolocation $geolocation = null)
    {
        $this->logger = $logger;
        $this->apiClient = $apiClient ?: new ApiClient($logger);
        $this->geolocation = $geolocation ?: new Geolocation();
        $this->adapter = $adapter ?: new TagAwareAdapter(new PhpFilesAdapter());
    }

    /**
     * Configure this instance.
     *
     * @param bool   $streamMode                If we use the stream mode (else we use the live mode)
     * @param string $apiUrl                    The URL of the LAPI
     * @param int    $timeout                   The timeout well calling LAPI
     * @param string $userAgent                 The user agent to use when calling LAPI
     * @param string $apiKey                    The Bouncer API Key to use to connect LAPI
     * @param int    $cacheExpirationForCleanIp The duration to cache an IP considered as clean by LAPI
     * @param int    $cacheExpirationForBadIp   The duration to cache an IP considered as bad by LAPI
     * @param string $fallbackRemediation       The remediation to use when the remediation sent by LAPI is not supported by
     *                                          this library
     *
     * @throws InvalidArgumentException
     */
    public function configure(
        bool $streamMode,
        string $apiUrl,
        int $timeout,
        string $userAgent,
        string $apiKey,
        array $cacheDurations,
        string $fallbackRemediation,
        array $geolocConfig = []
    ): void {
        $this->streamMode = $streamMode;
        $this->cacheExpirationForCleanIp =
            $cacheDurations['clean_ip_cache_duration'] ?? Constants::CACHE_EXPIRATION_FOR_CLEAN_IP;
        $this->cacheExpirationForBadIp =
            $cacheDurations['bad_ip_cache_duration'] ?? Constants::CACHE_EXPIRATION_FOR_BAD_IP;
        $this->cacheExpirationForCaptcha =
            $cacheDurations['captcha_cache_duration'] ?? Constants::CACHE_EXPIRATION_FOR_CAPTCHA;
        $this->cacheExpirationForGeo =
            $cacheDurations['geolocation_cache_duration'] ?? Constants::CACHE_EXPIRATION_FOR_GEO;
        $this->fallbackRemediation = $fallbackRemediation;
        $this->geolocConfig = $geolocConfig;
        $cacheConfigItem = $this->adapter->getItem('cacheConfig');
        $cacheConfig = $cacheConfigItem->get();
        $this->warmedUp = (\is_array($cacheConfig) && isset($cacheConfig['warmed_up'])
            && true === $cacheConfig['warmed_up']);
        $this->logger->debug('', [
            'type' => 'API_CACHE_INIT',
            'adapter' => \get_class($this->adapter),
            'mode' => ($streamMode ? 'stream' : 'live'),
            'exp_clean_ips' => $this->cacheExpirationForCleanIp,
            'exp_bad_ips' => $this->cacheExpirationForBadIp,
            'exp_captcha_flow' => $this->cacheExpirationForCaptcha,
            'exp_geolocation_result' => $this->cacheExpirationForGeo,
            'warmed_up' => ($this->warmedUp ? 'true' : 'false'),
            'geolocation' => $this->geolocConfig,
        ]);
        $this->apiClient->configure($apiUrl, $timeout, $userAgent, $apiKey);
    }

    /**
     * @return array
     */
    private function getScopes(): ?array
    {
        if (null === $this->scopes) {
            $finalScopes = [Constants::SCOPE_IP, Constants::SCOPE_RANGE];
            if (!empty($this->geolocConfig['enabled'])) {
                $finalScopes[] = Constants::SCOPE_COUNTRY;
            }
            $this->scopes = $finalScopes;
        }

        return $this->scopes;
    }

    /**
     * Add remediation to a Symfony Cache Item identified by a cache key.
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws \Psr\Cache\CacheException
     */
    private function addRemediationToCacheItem(string $cacheKey, string $type, int $expiration, int $decisionId): string
    {
        $item = $this->adapter->getItem(base64_encode($cacheKey));

        // Merge with existing remediations (if any).
        $remediations = $item->isHit() ? $item->get() : [];

        $index = array_search(Constants::REMEDIATION_BYPASS, array_column($remediations, 0));
        if (false !== $index) {
            $this->logger->debug('', [
                'type' => 'IP_CLEAN_TO_BAD',
                'cache_key' => $cacheKey,
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
        $item->tag(Constants::CACHE_TAG_REM);

        // Save the cache without committing it to the cache system.
        // Useful to improve performance when updating the cache.
        if (!$this->adapter->saveDeferred($item)) {
            throw new BouncerException("cache#$cacheKey: Unable to save this deferred item in cache: "."$type for $expiration sec, (decision $decisionId)");
        }

        return $prioritizedRemediations[0][0];
    }

    /**
     * Remove a decision from a Symfony Cache Item identified by a cache key.
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws \Psr\Cache\CacheException
     */
    private function removeDecisionFromRemediationItem(string $cacheKey, int $decisionId): bool
    {
        $item = $this->adapter->getItem(base64_encode($cacheKey));
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
                'cache_key' => $cacheKey,
            ]);
            $this->adapter->delete(base64_encode($cacheKey));

            return true;
        }
        // Build the item lifetime in cache and sort remediations by priority
        $maxLifetime = max(array_column($remediations, 1));
        $cacheContent = Remediation::sortRemediationByPriority($remediations);
        $item->expiresAt(new DateTime('@'.$maxLifetime));
        $item->set($cacheContent);
        $item->tag(Constants::CACHE_TAG_REM);

        // Save the cache without committing it to the cache system.
        // Useful to improve performance when updating the cache.
        if (!$this->adapter->saveDeferred($item)) {
            throw new BouncerException("cache#$cacheKey: Unable to save item");
        }
        $this->logger->debug('', [
            'type' => 'DECISION_REMOVED',
            'decision' => $decisionId,
            'cache_key' => $cacheKey,
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
            if ($this->streamMode) {
                // In stream mode we consider a clean IP forever... until the next resync.
                $duration = 315360000; // in this case, forever is 10 years as PHP_INT_MAX will cause trouble with the Memcached Adapter (int to float unwanted conversion)
            }

            return [Constants::REMEDIATION_BYPASS, $duration, 0];
        }

        $duration = self::parseDurationToSeconds($decision['duration']);

        // Don't set a max duration in stream mode to avoid bugs. Only the stream update has to change the cache state.
        if (!$this->streamMode) {
            $duration = min($this->cacheExpirationForBadIp, $duration);
        }

        return [
            $decision['type'], // ex: ban, captcha
            time() + $duration, // expiration timestamp
            $decision['id'],
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    private function defferUpdateCacheConfig(array $config): void
    {
        $cacheConfigItem = $this->adapter->getItem('cacheConfig');
        $cacheConfig = $cacheConfigItem->isHit() ? $cacheConfigItem->get() : [];
        $cacheConfig = array_replace_recursive($cacheConfig, $config);
        $cacheConfigItem->set($cacheConfig);
        $this->adapter->saveDeferred($cacheConfigItem);
    }

    /**
     * Update the cached remediation of the specified cacheKey from these new decisions.
     *
     * @throws InvalidArgumentException
     */
    private function saveRemediationsForCacheKey(array $decisions, string $cacheKey): string
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
                $remediationResult = $this->addRemediationToCacheItem($cacheKey, $type, $exp, $id);
            }
        } else {
            $remediation = $this->formatRemediationFromDecision(null);
            $type = $remediation[0];
            $exp = $remediation[1];
            $id = $remediation[2];
            $remediationResult = $this->addRemediationToCacheItem($cacheKey, $type, $exp, $id);
        }
        $this->commit();

        return $remediationResult;
    }

    /**
     * Cache key convention.
     *
     * @throws Exception
     */
    private function getCacheKey(string $scope, string $value): string
    {
        if (!isset($this->cacheKeys[$scope][$value])) {
            switch ($scope) {
                case Constants::SCOPE_RANGE:
                    $this->cacheKeys[$scope][$value] = Constants::SCOPE_IP.self::CACHE_SEP.$value;
                    break;
                case Constants::SCOPE_IP:
                case Constants::CACHE_TAG_GEO.self::CACHE_SEP.Constants::SCOPE_IP:
                case Constants::CACHE_TAG_CAPTCHA.self::CACHE_SEP.Constants::SCOPE_IP:
                case Constants::SCOPE_COUNTRY:
                    $this->cacheKeys[$scope][$value] = $scope.self::CACHE_SEP.$value;
                    break;
                default:
                    throw new BouncerException('Unknown scope:'.$scope);
            }
        }

        return $this->cacheKeys[$scope][$value];
    }

    /**
     * Update the cached remediations from these new decisions.
     *
     * @throws InvalidArgumentException
     * @throws Exception
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

            if (Constants::SCOPE_IP === $decision['scope']) {
                $address = Factory::parseAddressString($decision['value'], 3);
                if (null === $address) {
                    $this->logger->warning('', [
                        'type' => 'INVALID_IP_TO_ADD_FROM_REMEDIATION',
                        'decision' => $decision,
                    ]);
                    continue;
                }
                $cacheKey = $this->getCacheKey($decision['scope'], $address->toString());
                $this->addRemediationToCacheItem($cacheKey, $type, $exp, $id);
            } elseif (Constants::SCOPE_RANGE === $decision['scope']) {
                $range = Subnet::parseString($decision['value']);

                $addressType = $range->getAddressType();
                $isIpv6 = (Type::T_IPv6 === $addressType);
                if ($isIpv6 || ($range->getNetworkPrefix() < 24)) {
                    $error = ['type' => 'DECISION_RANGE_TO_ADD_IS_TOO_LARGE', 'decision' => $decision['id'], 'range' => $decision['value'], 'remediation' => $type, 'expiration' => $exp];
                    $errors[] = $error;
                    $this->logger->warning('', $error);
                    continue;
                }
                $comparableEndAddress = $range->getComparableEndString();
                $address = $range->getStartAddress();
                $cacheKey = $this->getCacheKey($decision['scope'], $address->toString());
                $this->addRemediationToCacheItem($cacheKey, $type, $exp, $id);
                $ipCount = 1;
                do {
                    $address = $address->getNextAddress();
                    $cacheKey = $this->getCacheKey($decision['scope'], $address->toString());
                    $this->addRemediationToCacheItem($cacheKey, $type, $exp, $id);
                    ++$ipCount;
                    if ($ipCount >= 1000) {
                        throw new BouncerException("Unable to store the decision ${$decision['id']}, the IP range: ${$decision['value']} is too large and can cause storage problem. Decision ignored.");
                    }
                } while (0 !== strcmp($address->getComparableString(), $comparableEndAddress));
            } elseif (Constants::SCOPE_COUNTRY === $decision['scope']) {
                $cacheKey = $this->getCacheKey($decision['scope'], $decision['value']);
                $this->addRemediationToCacheItem($cacheKey, $type, $exp, $id);
            }
        }

        return ['success' => $this->commit(), 'errors' => $errors];
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function removeRemediations(array $decisions): array
    {
        $errors = [];
        $count = 0;
        foreach ($decisions as $decision) {
            if (Constants::SCOPE_IP === $decision['scope']) {
                $address = Factory::parseAddressString($decision['value'], 3);
                if (null === $address) {
                    $this->logger->warning('', [
                        'type' => 'INVALID_IP_TO_REMOVE_FROM_REMEDIATION',
                        'decision' => $decision,
                    ]);
                    continue;
                }
                $cacheKey = $this->getCacheKey($decision['scope'], $address->toString());
                if (!$this->removeDecisionFromRemediationItem($cacheKey, $decision['id'])) {
                    $this->logger->debug('', ['type' => 'DECISION_TO_REMOVE_NOT_FOUND_IN_CACHE', 'decision' => $decision['id']]);
                } else {
                    $this->logger->debug('', [
                    'type' => 'DECISION_REMOVED',
                    'decision' => $decision['id'],
                    'value' => $decision['value'],
                    ]);
                    ++$count;
                }
            } elseif (Constants::SCOPE_RANGE === $decision['scope']) {
                $range = Subnet::parseString($decision['value']);

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
                $cacheKey = $this->getCacheKey($decision['scope'], $address->toString());
                if (!$this->removeDecisionFromRemediationItem($cacheKey, $decision['id'])) {
                    $this->logger->debug('', ['type' => 'DECISION_TO_REMOVE_NOT_FOUND_IN_CACHE', 'decision' => $decision['id']]);
                }
                $ipCount = 1;
                $success = true;
                do {
                    $address = $address->getNextAddress();
                    $cacheKey = $this->getCacheKey($decision['scope'], $address->toString());
                    if (!$this->removeDecisionFromRemediationItem($cacheKey, $decision['id'])) {
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
                        'scope' => $decision['scope'],
                        'value' => $decision['value'],
                        ]);
                    ++$count;
                } else {
                    // The API may return stale deletion events due to API design.
                    // Ignoring them is therefore not a problem.
                    $this->logger->debug('', ['type' => 'DECISION_TO_REMOVE_NOT_FOUND_IN_CACHE', 'decision' => $decision['id']]);
                }
            } elseif (Constants::SCOPE_COUNTRY === $decision['scope']) {
                $cacheKey = $this->getCacheKey($decision['scope'], $decision['value']);
                if (!$this->removeDecisionFromRemediationItem($cacheKey, $decision['id'])) {
                    $this->logger->debug('', ['type' => 'DECISION_TO_REMOVE_NOT_FOUND_IN_CACHE', 'decision' => $decision['id']]);
                } else {
                    $this->logger->debug('', [
                        'type' => 'DECISION_REMOVED',
                        'decision' => $decision['id'],
                        'value' => $decision['value'],
                    ]);
                    ++$count;
                }
            }
        }

        $this->commit();

        return ['count' => $count, 'errors' => $errors];
    }

    /**
     * @throws InvalidArgumentException
     */
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
     *
     * @throws InvalidArgumentException
     */
    public function warmUp(): array
    {
        $addErrors = [];

        if ($this->warmedUp) {
            $this->clear();
        }
        $this->logger->debug('', ['type' => 'START_CACHE_WARMUP']);
        $decisionsDiff = $this->apiClient->getStreamedDecisions(true, $this->getScopes());
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
     *
     * @throws InvalidArgumentException
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
        $decisionsDiff = $this->apiClient->getStreamedDecisions(false, $this->getScopes());
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
     * This method is called when nothing has been found in cache for the requested value/cacheScope pair (IP,country).
     * In live mode is enabled, calls the API for decisions concerning the specified IP
     * In stream mode, as we consider cache is the single source of truth, the value is considered clean.
     * Finally, the result is stored in caches for further calls.
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function miss(string $value, string $cacheScope): string
    {
        $decisions = [];
        $cacheKey = $this->getCacheKey($cacheScope, $value);
        if (!$this->streamMode) {
            if (Constants::SCOPE_IP === $cacheScope) {
                $this->logger->debug('', ['type' => 'DIRECT_API_CALL', 'ip' => $value]);
                $decisions = $this->apiClient->getFilteredDecisions(['ip' => $value]);
            } elseif (Constants::SCOPE_COUNTRY === $cacheScope) {
                $this->logger->debug('', ['type' => 'DIRECT_API_CALL', 'country' => $value]);
                $decisions = $this->apiClient->getFilteredDecisions([
                    'scope' => Constants::SCOPE_COUNTRY,
                    'value' => $value,
                ]);
            }
        }

        return $this->saveRemediationsForCacheKey($decisions, $cacheKey);
    }

    /**
     * Used in both mode (stream and rupture).
     * This method formats the cached item as a remediation.
     * It returns the highest remediation level found.
     *
     * @throws InvalidArgumentException
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
     * @param $value
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function handleCacheRemediation(string $cacheScope, $value): string
    {
        $cacheKey = $this->getCacheKey($cacheScope, $value);
        if ($this->adapter->hasItem(base64_encode($cacheKey))) {
            $remediation = $this->hit($cacheKey);
            $cache = 'hit';
        } else {
            $remediation = $this->miss($value, $cacheScope);
            $cache = 'miss';
        }

        if (Constants::REMEDIATION_BYPASS === $remediation) {
            $this->logger->info('', ['type' => 'CLEAN_VALUE', 'scope' => $cacheScope, 'value' => $value, 'cache' => $cache]);
        } else {
            $this->logger->warning('', [
                'type' => 'BAD_VALUE',
                'value' => $value,
                'scope' => $cacheScope,
                'remediation' => $remediation,
                'cache' => $cache,
            ]);
        }

        return $remediation;
    }

    /**
     * Request the cache for the specified IP.
     *
     * @return string the computed remediation string, or null if no decision was found
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function get(AddressInterface $address): string
    {
        $ip = $address->toString();
        $this->logger->debug('', ['type' => 'START_IP_CHECK', 'ip' => $ip]);

        if ($this->streamMode && !$this->warmedUp) {
            throw new BouncerException('CrowdSec Bouncer configured in "stream" mode. Please warm the cache up before trying to access it.');
        }

        // Handle Ip and Range remediation
        $remediations = [[$this->handleCacheRemediation(Constants::SCOPE_IP, $ip), '', '']];

        // Handle Geolocation remediation
        if (!empty($this->geolocConfig['enabled'])) {
            $countryResult = $this->geolocation->getCountryResult($this->geolocConfig, $ip, $this);
            $countryToQuery = null;
            if (!empty($countryResult['country'])) {
                $countryToQuery = $countryResult['country'];
                $this->logger->debug('', ['type' => 'GEOLOCALISED_COUNTRY', 'ip' => $ip, 'country' => $countryToQuery]);
            } elseif (!empty($countryResult['not_found'])) {
                $this->logger->warning('', [
                    'type' => 'IP_NOT_FOUND_WHILE_GETTING_GEOLOC_COUNTRY',
                    'ip' => $ip,
                    'error' => $countryResult['not_found'],
                ]);
            } elseif (!empty($countryResult['error'])) {
                $this->logger->warning('', [
                    'type' => 'ERROR_WHILE_GETTING_GEOLOC_COUNTRY',
                    'ip' => $ip,
                    'error' => $countryResult['error'],
                ]);
            }
            if ($countryToQuery) {
                $remediations[] = [$this->handleCacheRemediation(Constants::SCOPE_COUNTRY, $countryToQuery), '', ''];
            }
        }
        $prioritizedRemediations = Remediation::sortRemediationByPriority($remediations);
        $finalRemediation = $prioritizedRemediations[0][0];
        $this->logger->info('', ['type' => 'FINAL_REMEDIATION', 'ip' => $ip, 'remediation' => $finalRemediation]);

        return $finalRemediation;
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
     * When Memcached connection fail, it throws an unhandled warning.
     * To catch this warning as a clean exception we have to temporarily change the error handler.
     */
    private function setCustomErrorHandler(): void
    {
        if ($this->adapter instanceof MemcachedAdapter) {
            set_error_handler(function ($errno, $errstr) {
                throw new BouncerException("Error when connecting to Memcached. (Error level: $errno) Please fix the Memcached DSN or select another cache technology. Original message was: $errstr");
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
     * @throws BouncerException|InvalidArgumentException if the connection was not successful
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

    /**
     * Retrieve raw cache item for some IP and cache tag.
     *
     * @return array|mixed
     *
     * @throws InvalidArgumentException
     */
    private function getIpCachedVariables(string $cacheTag, string $ip)
    {
        $cacheKey = $this->getCacheKey($cacheTag.self::CACHE_SEP.Constants::SCOPE_IP, $ip);
        $cachedVariables = [];
        if ($this->adapter->hasItem(base64_encode($cacheKey))) {
            $cachedVariables = $this->adapter->getItem(base64_encode($cacheKey))->get();
        }

        return $cachedVariables;
    }

    /**
     * Retrieved prepared cached variables associated to an Ip
     * Set null if not already in cache.
     *
     * @param $cacheTag
     * @param $names
     * @param $ip
     *
     * @return array
     */
    public function getIpVariables($cacheTag, $names, $ip)
    {
        $cachedVariables = $this->getIpCachedVariables($cacheTag, $ip);
        $variables = [];
        foreach ($names as $name) {
            $variables[$name] = null;
            if (isset($cachedVariables[$name])) {
                $variables[$name] = $cachedVariables[$name];
            }
        }

        return $variables;
    }

    /**
     * Store variables in cache for some IP and cache tag.
     *
     * @return void
     *
     * @throws InvalidArgumentException
     * @throws \Psr\Cache\CacheException
     * @throws Exception
     */
    public function setIpVariables(string $cacheTag, array $pairs, string $ip)
    {
        $cacheKey = $this->getCacheKey($cacheTag.self::CACHE_SEP.Constants::SCOPE_IP, $ip);
        $cachedVariables = $this->getIpCachedVariables($cacheTag, $ip);
        foreach ($pairs as $name => $value) {
            $cachedVariables[$name] = $value;
        }
        $duration = (Constants::CACHE_TAG_CAPTCHA === $cacheTag)
            ? $this->cacheExpirationForCaptcha : $this->cacheExpirationForGeo;
        $item = $this->adapter->getItem(base64_encode($cacheKey));
        $item->set($cachedVariables);
        $item->expiresAt(new DateTime("+$duration seconds"));
        $item->tag($cacheTag);
        $this->adapter->save($item);
    }

    /**
     * Unset cached variables for some IP and cache tag.
     *
     * @return void
     *
     * @throws InvalidArgumentException
     * @throws \Psr\Cache\CacheException
     */
    public function unsetIpVariables(string $cacheTag, array $pairs, string $ip)
    {
        $cacheKey = $this->getCacheKey($cacheTag.self::CACHE_SEP.Constants::SCOPE_IP, $ip);
        $cachedVariables = $this->getIpCachedVariables($cacheTag, $ip);
        foreach ($pairs as $name => $value) {
            unset($cachedVariables[$name]);
        }
        $duration = (Constants::CACHE_TAG_CAPTCHA === $cacheTag)
            ? $this->cacheExpirationForCaptcha : $this->cacheExpirationForGeo;
        $item = $this->adapter->getItem(base64_encode($cacheKey));
        $item->set($cachedVariables);
        $item->expiresAt(new DateTime("+$duration seconds"));
        $item->tag($cacheTag);
        $this->adapter->save($item);
    }
}
