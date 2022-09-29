<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

use Exception;
use IPLib\Address\AddressInterface;
use IPLib\Address\Type;
use IPLib\Factory;
use IPLib\Range\Subnet;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\PruneableInterface;

/**
 * The cache mechanism class to handle decisions from Local API.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class ApiCache extends AbstractCache
{
    /**
     * @throws InvalidArgumentException|BouncerException
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
     * Request the cache for the specified IP.
     *
     * @return string the computed remediation string, or null if no decision was found
     *
     * @throws InvalidArgumentException
     * @throws Exception|CacheException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function get(AddressInterface $address): string
    {
        $ip = $address->toString();
        $this->logger->debug('', ['type' => 'START_IP_CHECK', 'ip' => $ip]);

        if ($this->streamMode && !$this->warmedUp) {
            $message = 'CrowdSec Bouncer configured in "stream" mode. ' .
                       'Please warm the cache up before trying to access it.';
            throw new BouncerException($message);
        }

        // Handle Ip and Range remediation
        $remediations = [[$this->handleCacheRemediation(Constants::SCOPE_IP, $ip), '', '']];

        // Handle Geolocation remediation
        if (!empty($this->geolocConfig['enabled'])) {
            $geolocation = new Geolocation();
            $countryToQuery = $geolocation->getCountryToQuery($this->geolocConfig, $ip, $this, $this->logger);
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
     * @throws BouncerException
     */
    public function prune(): bool
    {
        if ($this->adapter instanceof PruneableInterface) {
            $pruned = $this->adapter->prune();
            $this->logger->debug('', ['type' => 'CACHE_PRUNED']);

            return $pruned;
        }

        throw new BouncerException('Cache Adapter' . \get_class($this->adapter) . ' is not prunable.');
    }

    /**
     * Used in stream mode only.
     * Pull decisions updates from the API and update the cached remediations.
     * Used for the stream mode when we have to update the remediations list.
     *
     * @return array number of deleted and new decisions, and errors when processing decisions
     *
     * @throws InvalidArgumentException|CacheException|BouncerException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function pullUpdates(): array
    {
        $deletionErrors = [];
        $addErrors = [];
        if (!$this->warmedUp) {
            $warmUpResult = $this->warmUp();
            $addErrors = $warmUpResult['errors'];

            return [
                'deleted' => 0,
                'new' => $warmUpResult['count'],
                'deletionErrors' => $deletionErrors,
                'addErrors' => $addErrors
            ];
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
            if (!empty($saveResult['success'])) {
                $addErrors = $saveResult['errors'] ?? 0;
                $nbNew = $saveResult['count'] ?? 0;
            } else {
                $this->logger->warning('', [
                    'type' => 'CACHE_UPDATED_FAILED',
                    'message' => 'Something went wrong while committing to cache adapter']);
            }
        }

        $this->logger->debug('', ['type' => 'CACHE_UPDATED', 'deleted' => $nbDeleted, 'new' => $nbNew]);

        return [
            'deleted' => $nbDeleted,
            'new' => $nbNew,
            'deletionErrors' => $deletionErrors,
            'addErrors' => $addErrors
        ];
    }

    /**
     * Used in stream mode only.
     * Warm the cache up.
     * Used when the stream mode has just been activated.
     *
     * @return array "count": number of decisions added, "errors": decisions not added
     *
     * @throws InvalidArgumentException|CacheException|BouncerException
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
     * @param string $cacheScope
     * @param string $value
     *
     * @return string
     * @throws InvalidArgumentException
     * @throws CacheException|BouncerException
     */
    private function handleCacheRemediation(string $cacheScope, string $value): string
    {
        $cacheKey = $this->getCacheKey($cacheScope, $value);
        $hasItem = $this->adapter->hasItem(base64_encode($cacheKey));
        $remediation = $hasItem ? $this->hit($cacheKey) : $this->miss($value, $cacheScope);
        $cache = $hasItem ? 'hit' : 'miss';
        $this->logRemediation($cacheScope, $value, $cache, $remediation);

        return $remediation;
    }

    /**
     * @param string $cacheKey
     * @param array $decision
     * @param int $count
     * @return void
     * @throws InvalidArgumentException
     * @throws CacheException
     *
     */
    private function handleDecisionRemoving(string $cacheKey, array $decision, int &$count): void
    {
        if (!$this->removeDecisionFromRemediationItem($cacheKey, $decision['id'])) {
            $this->logger->debug('', [
                'type' => 'DECISION_TO_REMOVE_NOT_FOUND_IN_CACHE',
                'decision' => $decision['id']
            ]);

            return;
        }
        $this->logger->debug('', [
            'type' => 'DECISION_REMOVED',
            'decision' => $decision['id'],
            'value' => $decision['value'],
        ]);
        ++$count;
    }

    /**
     * @param array $decision
     * @param bool $success
     * @param int $count
     * @return void
     */
    private function handleSuccess(array $decision, bool $success, int &$count): void
    {
        if ($success) {
            $this->logger->debug('', [
                'type' => 'DECISION_REMOVED',
                'decision' => $decision['id'],
                'scope' => $decision['scope'],
                'value' => $decision['value'],
            ]);
            ++$count;
            return;
        }
        // The API may return stale deletion events due to API design.
        // Ignoring them is therefore not a problem.
        $this->logger->debug('', [
            'type' => 'DECISION_TO_REMOVE_NOT_FOUND_IN_CACHE',
            'decision' => $decision['id']
        ]);
    }

    /**
     * @param string $cacheScope
     * @param string $value
     * @param string $cache
     * @param string $remediation
     * @return void
     */
    private function logRemediation(string $cacheScope, string $value, string $cache, string $remediation): void
    {
        if (Constants::REMEDIATION_BYPASS === $remediation) {
            $this->logger->info('', [
                'type' => 'CLEAN_VALUE',
                'scope' => $cacheScope,
                'value' => $value,
                'cache' => $cache
            ]);

            return;
        }

        $this->logger->warning('', [
            'type' => 'BAD_VALUE',
            'value' => $value,
            'scope' => $cacheScope,
            'remediation' => $remediation,
            'cache' => $cache,
        ]);
    }

    /**
     * @param array $decision
     * @param int $count
     * @return void
     * @throws BouncerException
     * @throws InvalidArgumentException
     * @throws CacheException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function removeCountryScopedDecision(array $decision, int &$count): void
    {
        $cacheKey = $this->getCacheKey(Constants::SCOPE_COUNTRY, $decision['value']);
        $this->handleDecisionRemoving($cacheKey, $decision, $count);
    }

    /**
     * @param array $decision
     * @param int $count
     * @return void
     * @throws BouncerException
     * @throws InvalidArgumentException
     * @throws CacheException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function removeIpScopedDecision(array $decision, int &$count): void
    {
        $address = Factory::parseAddressString($decision['value'], 3);
        if (null === $address) {
            $this->logger->warning('', [
                'type' => 'INVALID_IP_TO_REMOVE_FROM_REMEDIATION',
                'decision' => $decision,
            ]);
            return;
        }
        $cacheKey = $this->getCacheKey(Constants::SCOPE_IP, $address->toString());
        $this->handleDecisionRemoving($cacheKey, $decision, $count);
    }

    /**
     * @param array $decision
     * @param int $count
     * @param array $errors
     * @return void
     * @throws BouncerException
     * @throws InvalidArgumentException
     * @throws CacheException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function removeRangeScopedDecision(array $decision, int &$count, array &$errors): void
    {
        $range = Subnet::parseString($decision['value']);
        if (null === $range) {
            $this->logger->warning('', [
                'type' => 'INVALID_RANGE_TO_REMOVE_FROM_REMEDIATION',
                'decision' => $decision,
            ]);
            return;
        }
        $addressType = $range->getAddressType();
        $isIpv6 = (Type::T_IPv6 === $addressType);
        if ($isIpv6 || ($range->getNetworkPrefix() < 24)) {
            $error =
                [
                    'type' => 'DECISION_RANGE_TO_REMOVE_IS_TOO_LARGE',
                    'decision' => $decision['id'],
                    'range' => $decision['value']
                ];
            $errors[] = $error;
            $this->logger->warning('', $error);
            return;
        }

        $comparableEndAddress = $range->getComparableEndString();
        $address = $range->getStartAddress();
        $cacheKey = $this->getCacheKey($decision['scope'], $address->toString());
        if (!$this->removeDecisionFromRemediationItem($cacheKey, $decision['id'])) {
            $this->logger->debug('', [
                'type' => 'DECISION_TO_REMOVE_NOT_FOUND_IN_CACHE',
                'decision' => $decision['id']
            ]);
        }
        $ipCount = 1;
        $success = true;
        do {
            $address = $address->getNextAddress();
            if (null === $address) {
                $this->logger->warning('', [
                    'type' => 'INVALID_NEXT_ADDRESS_TO_REMOVE_FROM_REMEDIATION',
                    'decision' => $decision,
                ]);
                break;
            }
            $cacheKey = $this->getCacheKey(Constants::SCOPE_RANGE, $address->toString());
            if (!$this->removeDecisionFromRemediationItem($cacheKey, $decision['id'])) {
                $success = false;
            }
            ++$ipCount;
            if ($ipCount >= 1000) {
                $message = 'Unable to store the decision ' . $decision['id'] .
                           ', the IP range: ' . $decision['value'] .
                           ' is too large and can cause storage problem. Decision ignored.';
                throw new BouncerException($message);
            }
        } while (0 !== strcmp($address->getComparableString(), $comparableEndAddress));

        $this->handleSuccess($decision, $success, $count);
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception|CacheException
     */
    private function removeRemediations(array $decisions): array
    {
        $errors = [];
        $count = 0;
        foreach ($decisions as $decision) {
            $this->removeSingleDecision($decision, $count, $errors);
        }

        $this->commit();

        return ['count' => $count, 'errors' => $errors];
    }

    /**
     * @param array $decision
     * @param int $count
     * @param array $errors
     * @return void
     * @throws BouncerException
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    private function removeSingleDecision(array $decision, int &$count, array &$errors)
    {
        switch ($decision['scope']) {
            case Constants::SCOPE_IP:
                $this->removeIpScopedDecision($decision, $count);
                break;

            case Constants::SCOPE_RANGE:
                $this->removeRangeScopedDecision($decision, $count, $errors);
                break;

            case Constants::SCOPE_COUNTRY:
                $this->removeCountryScopedDecision($decision, $count);
                break;
            default:
                break;
        }
    }

    /**
     * @param array $decision
     * @param int $count
     * @param string $type
     * @param int $exp
     * @param int $id
     * @return void
     * @throws BouncerException
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    private function saveCountryScopedDecision(array $decision, int &$count, string $type, int $exp, int $id): void
    {
        $cacheKey = $this->getCacheKey(Constants::SCOPE_COUNTRY, $decision['value']);
        $this->addRemediationToCacheItem($cacheKey, $type, $exp, $id);
        ++$count;
    }

    /**
     * @param array $decision
     * @param int $count
     * @param string $type
     * @param int $exp
     * @param int $id
     * @return void
     * @throws BouncerException
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    private function saveIpScopedDecision(array $decision, int &$count, string $type, int $exp, int $id): void
    {
        $address = Factory::parseAddressString($decision['value'], 3);
        if (null === $address) {
            $this->logger->warning('', [
                'type' => 'INVALID_IP_TO_ADD_FROM_REMEDIATION',
                'decision' => $decision,
            ]);
            return;
        }
        $cacheKey = $this->getCacheKey(Constants::SCOPE_IP, $address->toString());
        $this->addRemediationToCacheItem($cacheKey, $type, $exp, $id);
        ++$count;
    }

    /**
     * @param array $decision
     * @param int $count
     * @param array $errors
     * @param string $type
     * @param int $exp
     * @param int $id
     * @return void
     * @throws BouncerException
     * @throws InvalidArgumentException
     * @throws CacheException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function saveRangeScopedDecision(
        array $decision,
        int &$count,
        array &$errors,
        string $type,
        int $exp,
        int $id
    ): void {
        $range = Subnet::parseString($decision['value']);
        if (null === $range) {
            $this->logger->warning('', [
                'type' => 'INVALID_RANGE_TO_ADD_FROM_REMEDIATION',
                'decision' => $decision,
            ]);
            return;
        }
        $addressType = $range->getAddressType();
        $isIpv6 = (Type::T_IPv6 === $addressType);
        if ($isIpv6 || ($range->getNetworkPrefix() < 24)) {
            $error =
                [
                    'type' => 'DECISION_RANGE_TO_ADD_IS_TOO_LARGE',
                    'decision' => $decision['id'],
                    'range' => $decision['value'],
                    'remediation' => $type,
                    'expiration' => $exp
                ];
            $errors[] = $error;
            $this->logger->warning('', $error);
            return;
        }
        $comparableEndAddress = $range->getComparableEndString();
        $address = $range->getStartAddress();
        $cacheKey = $this->getCacheKey($decision['scope'], $address->toString());
        $this->addRemediationToCacheItem($cacheKey, $type, $exp, $id);
        $ipCount = 1;
        do {
            $address = $address->getNextAddress();
            if (null === $address) {
                $this->logger->warning('', [
                    'type' => 'INVALID_NEXT_ADDRESS_TO_REMOVE_FROM_REMEDIATION',
                    'decision' => $decision,
                ]);
                break;
            }
            $cacheKey = $this->getCacheKey(Constants::SCOPE_RANGE, $address->toString());
            $this->addRemediationToCacheItem($cacheKey, $type, $exp, $id);
            ++$ipCount;
            if ($ipCount >= 1000) {
                $message = 'Unable to store the decision ' . $decision['id'] .
                           ', the IP range: ' . $decision['value'] .
                           ' is too large and can cause storage problem. Decision ignored.';
                throw new BouncerException($message);
            }
        } while (0 !== strcmp($address->getComparableString(), $comparableEndAddress));
        ++$count;
    }

    /**
     * Update the cached remediations from these new decisions.
     *
     * @throws InvalidArgumentException
     * @throws Exception|CacheException
     *
     */
    private function saveRemediations(array $decisions): array
    {
        $errors = [];
        $count = 0;
        foreach ($decisions as $decision) {
            $this->saveSingleDecision($decision, $count, $errors);
        }

        return ['success' => $this->commit(), 'errors' => $errors, 'count' => $count];
    }

    /**
     * @param array $decision
     * @param int $count
     * @param array $errors
     * @return void
     * @throws BouncerException
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    private function saveSingleDecision(array $decision, int &$count, array &$errors)
    {
        $remediation = $this->formatRemediationFromDecision($decision);
        if (!\in_array($remediation[0], Constants::ORDERED_REMEDIATIONS)) {
            $this->logger->warning('', [
                'type' => 'UNKNOWN_REMEDIATION',
                'unknown' => $remediation[0],
                'fallback' => $this->fallbackRemediation]);
            $remediation[0] = $this->fallbackRemediation;
        }
        $type = (string) $remediation[0];
        $exp = (int) $remediation[1];
        $id = (int) $remediation[2];
        switch ($decision['scope']) {
            case Constants::SCOPE_IP:
                $this->saveIpScopedDecision($decision, $count, $type, $exp, $id);
                break;

            case Constants::SCOPE_RANGE:
                $this->saveRangeScopedDecision($decision, $count, $errors, $type, $exp, $id);
                break;

            case Constants::SCOPE_COUNTRY:
                $this->saveCountryScopedDecision($decision, $count, $type, $exp, $id);
                break;

            default:
                break;
        }
    }
}
