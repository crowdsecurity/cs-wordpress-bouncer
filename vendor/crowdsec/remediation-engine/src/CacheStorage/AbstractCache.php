<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\CacheStorage;

use CrowdSec\RemediationEngine\Constants;
use CrowdSec\RemediationEngine\Decision;
use IPLib\Address\Type;
use IPLib\Factory;
use IPLib\Range\RangeInterface;
use IPLib\Range\Subnet;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\PruneableInterface;

abstract class AbstractCache
{
    /** @var string Internal name for cache config item */
    public const CONFIG = 'config';
    /** @var string Internal name for deferred cache item */
    public const DEFER = 'deferred';
    /** @var string Internal name for effective saved cache item (not deferred) */
    public const DONE = 'done';
    /** @var string The cache key prefix or tag for a geolocation */
    public const GEOLOCATION = 'geolocation';
    /** @var int Cache item content array expiration index */
    public const INDEX_EXP = 1;
    /** @var int Cache item content array identifier index */
    public const INDEX_ID = 2;
    /** @var int Cache item content array main value index */
    public const INDEX_MAIN = 0;
    /** @var int Cache item content array origin index */
    public const INDEX_ORIGIN = 3;
    /** @var string The cache key prefix for a IPV4 range bucket */
    public const IPV4_BUCKET_KEY = 'range_bucket_ipv4';
    /** @var string Internal name for last pull */
    public const LAST_PULL = 'last_pull';
    /** @var string Internal name for list */
    public const LIST = 'list';
    /** @var string Internal name for cache remediation origin count item */
    public const ORIGINS_COUNT = 'origins_count';
    /** @var string Internal name for cache clean item */
    public const CLEAN = 'clean';
    /** @var string Internal name for removed item index */
    public const REMOVED = 'removed';
    /** @var string Cache symbol */
    public const SEP = '_';
    /** @var string Internal name for stored item index */
    public const STORED = 'stored';
    /** @var string Internal name for warm up config item */
    public const WARMUP = 'warmed_up';
    /** @var string Cache tag for remediation */
    private const CACHE_TAG_REM = 'remediation';
    /** @var int The size of ipv4 range cache bucket */
    private const IPV4_BUCKET_SIZE = 256;
    /** @var string The message for not implemented scope */
    private const NOT_IMPLEMENTED_SCOPE = 'This scope is not yet implemented';
    /** @var string The cache tag for range bucket cache item */
    private const RANGE_BUCKET_TAG = 'range_bucket';
    /** @var AdapterInterface */
    protected $adapter;
    /**
     * @var array
     */
    protected $configs;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var array
     */
    private $cacheKeys = [];

    public function __construct(array $configs, AdapterInterface $adapter, LoggerInterface $logger = null)
    {
        $this->configs = $configs;
        $this->adapter = $adapter;
        if (!$logger) {
            $logger = new Logger('null');
            $logger->pushHandler(new NullHandler());
        }
        $this->logger = $logger;
        $this->logger->debug('Instantiate cache', [
            'type' => 'CACHE_INIT',
            'configs' => $configs,
            'adapter' => \get_class($adapter),
        ]);
    }

    public function cleanCachedValues(array $cachedValues): array
    {
        foreach ($cachedValues as $key => $cachedValue) {
            // Remove expired value
            $currentTime = time();
            if ($currentTime > $cachedValue[self::INDEX_EXP]) {
                unset($cachedValues[$key]);
            }
        }

        // Re-index starting from 0
        return array_values($cachedValues);
    }

    /**
     * Deletes all items in the pool.
     */
    public function clear(): bool
    {
        $result = $this->adapter->clear();
        $this->logger->info('Clearing cache', [
            'type' => 'REM_CACHE_CLEAR',
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Persists any deferred cache items.
     */
    public function commit(): bool
    {
        return $this->adapter->commit();
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Cache key convention.
     */
    public function getCacheKey(string $prefix, string $value): string
    {
        if (!isset($this->cacheKeys[$prefix][$value])) {
            $result = $prefix . self::SEP . $value;
            /**
             * Replace unauthorized symbols.
             *
             * @see https://symfony.com/doc/current/components/cache/cache_items.html#cache-item-keys-and-values
             */
            $this->cacheKeys[$prefix][$value] = preg_replace('/[^A-Za-z0-9_.]/', self::SEP, $result);
        }

        return $this->cacheKeys[$prefix][$value];
    }

    /**
     * Retrieve a config value by name. Return null if no set.
     */
    public function getConfig(string $name)
    {
        return (isset($this->configs[$name])) ? $this->configs[$name] : null;
    }

    /**
     * Retrieved prepared cached variables associated to an Ip
     * Set null if not already in cache.
     *
     * @throws InvalidArgumentException
     */
    public function getIpVariables(string $prefix, array $names, string $ip): array
    {
        $cachedVariables = $this->getIpCachedVariables($prefix, $ip);
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
     * @throws InvalidArgumentException
     */
    public function getItem(string $cacheKey): CacheItemInterface
    {
        return $this->adapter->getItem(base64_encode($cacheKey));
    }

    /**
     * Prune (delete) of all expired cache items.
     *
     * @throws CacheStorageException
     */
    public function prune(): bool
    {
        if ($this->adapter instanceof PruneableInterface) {
            return $this->adapter->prune();
        }

        throw new CacheStorageException('Cache Adapter ' . \get_class($this->adapter) . ' can not be pruned.');
    }

    /**
     * @throws CacheException
     * @throws CacheStorageException
     * @throws InvalidArgumentException
     */
    public function removeDecision(Decision $decision): array
    {
        $result = [self::DONE => 0, self::DEFER => 0, self::REMOVED => []];
        switch ($decision->getScope()) {
            case Constants::SCOPE_IP:
            case Constants::SCOPE_COUNTRY:
                $result = $this->remove($decision);
                break;
            case Constants::SCOPE_RANGE:
                $result = $this->handleRangeScoped($decision, self::REMOVED, [$this, 'remove']);
                break;
            default:
                $this->logger->warning(self::NOT_IMPLEMENTED_SCOPE, [
                    'type' => 'REM_CACHE_REMOVE_NON_IMPLEMENTED_SCOPE',
                    'decision' => $decision->toArray(),
                ]);
                break;
        }

        return $result;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function retrieveDecisionsForCountry(string $country): array
    {
        $cachedDecisions = [self::STORED => []];
        $cacheKey = $this->getCacheKey(Constants::SCOPE_COUNTRY, $country);
        $item = $this->getItem($cacheKey);
        if ($item->isHit()) {
            $cachedDecisions[self::STORED] = $item->get();
        }

        return $cachedDecisions;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @throws CacheStorageException
     * @throws InvalidArgumentException
     */
    public function retrieveDecisionsForIp(string $scope, string $ip): array
    {
        $cachedDecisions = [self::STORED => []];
        switch ($scope) {
            case Constants::SCOPE_IP:
                $cacheKey = $this->getCacheKey($scope, $ip);
                $item = $this->getItem($cacheKey);
                if ($item->isHit()) {
                    $cachedDecisions[self::STORED] = $item->get();
                }
                break;
            case Constants::SCOPE_RANGE:
                $bucketInt = $this->getRangeIntForIp($ip);
                $bucketCacheKey = $this->getCacheKey(self::IPV4_BUCKET_KEY, (string) $bucketInt);
                $bucketItem = $this->getItem($bucketCacheKey);
                $cachedBuckets = $bucketItem->isHit() ? $bucketItem->get() : [];
                foreach ($cachedBuckets as $cachedBucket) {
                    $rangeString = $cachedBucket[self::INDEX_MAIN];
                    $address = Factory::parseAddressString($ip);
                    $range = Factory::parseRangeString($rangeString);
                    if ($address && $range && $range->contains($address)) {
                        $cacheKey = $this->getCacheKey(Constants::SCOPE_RANGE, $rangeString);
                        $item = $this->getItem($cacheKey);
                        if ($item->isHit()) {
                            $cachedDecisions[self::STORED] = $item->get();
                        }
                    }
                }
                break;
            default:
                $this->logger->warning(self::NOT_IMPLEMENTED_SCOPE, [
                    'type' => 'REM_CACHE_RETRIEVE_FOR_IP_NON_IMPLEMENTED_SCOPE',
                    'scope' => $scope,
                ]);
                break;
        }

        return $cachedDecisions;
    }

    /**
     * Store variables in cache for some IP.
     *
     * @return void
     *
     * @throws CacheException
     * @throws InvalidArgumentException
     * @throws \Symfony\Component\Cache\Exception\InvalidArgumentException
     */
    public function setIpVariables(string $cacheScope, array $pairs, string $ip, int $duration, array $tags = [])
    {
        $cacheKey = $this->getCacheKey($cacheScope, $ip);
        $cachedVariables = $this->getIpCachedVariables($cacheScope, $ip);
        foreach ($pairs as $name => $value) {
            $cachedVariables[$name] = $value;
        }
        $this->saveItemWithDuration($cacheKey, $cachedVariables, $duration, $tags);
    }

    /**
     * @throws CacheException
     * @throws CacheStorageException
     * @throws InvalidArgumentException
     */
    public function storeDecision(Decision $decision): array
    {
        $result = [self::DONE => 0, self::DEFER => 0, self::STORED => []];
        switch ($decision->getScope()) {
            case Constants::SCOPE_IP:
            case Constants::SCOPE_COUNTRY:
                $result = $this->store($decision);
                break;
            case Constants::SCOPE_RANGE:
                $result = $this->handleRangeScoped($decision, self::STORED, [$this, 'store']);
                break;
            default:
                $this->logger->warning(self::NOT_IMPLEMENTED_SCOPE, [
                    'type' => 'REM_CACHE_STORE_NON_IMPLEMENTED_SCOPE',
                    'decision' => $decision->toArray(),
                ]);
        }

        return $result;
    }

    /**
     * Unset variables in cache for some IP.
     *
     * @return void
     *
     * @throws CacheException
     * @throws InvalidArgumentException
     * @throws \Symfony\Component\Cache\Exception\InvalidArgumentException
     */
    public function unsetIpVariables(string $cacheScope, array $names, string $ip, int $duration, array $tags = [])
    {
        $cacheKey = $this->getCacheKey($cacheScope, $ip);
        $cachedVariables = $this->getIpCachedVariables($cacheScope, $ip);
        foreach ($names as $name) {
            unset($cachedVariables[$name]);
        }
        $this->saveItemWithDuration($cacheKey, $cachedVariables, $duration, $tags);
    }

    /**
     * Create or update an item; Only passed content is updated.
     *
     * @throws InvalidArgumentException|CacheException
     */
    public function upsertItem(
        string $cacheKey,
        array $content,
        int $duration = 0,
        array $tags = []
    ): bool {
        $cacheItem = $this->getItem($cacheKey);
        $itemContent = $cacheItem->isHit() ? $cacheItem->get() : [];
        $content = array_replace_recursive($itemContent, $content);
        $cacheItem->set($content);
        if ($duration > 0) {
            $cacheItem->expiresAt(new \DateTime("+$duration seconds"));
        }
        if ($tags && $this->adapter instanceof TagAwareAdapterInterface) {
            $cacheItem->tag($tags);
        }

        return $this->adapter->save($cacheItem);
    }

    protected function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->adapter->saveDeferred($item);
    }

    /**
     * Format decision to use a minimal amount of data (less cache data consumption).
     */
    private function format(Decision $decision, int $bucketInt = null): array
    {
        $mainValue = $bucketInt ? $decision->getValue() : $decision->getType();

        return [
            self::INDEX_MAIN => $mainValue,
            self::INDEX_EXP => $decision->getExpiresAt(),
            self::INDEX_ID => $decision->getIdentifier(),
            self::INDEX_ORIGIN => $decision->getOrigin(),
        ];
    }

    /**
     * Check if some identifier is already cached.
     */
    private function getCachedIndex(string $identifier, array $cachedValues): ?int
    {
        $result = array_search($identifier, array_column($cachedValues, self::INDEX_ID), true);

        return false === $result ? null : $result;
    }

    /**
     * Retrieve raw cache item for some IP and cache scope.
     *
     * @return array|mixed
     *
     * @throws InvalidArgumentException
     */
    private function getIpCachedVariables(string $prefix, string $ip): array
    {
        $cacheKey = $this->getCacheKey($prefix, $ip);
        $cachedVariables = [];
        if ($this->adapter->hasItem(base64_encode($cacheKey))) {
            $cachedVariables = $this->adapter->getItem(base64_encode($cacheKey))->get();
        }

        return $cachedVariables;
    }

    private function getMaxExpiration(array $itemsToCache): int
    {
        $exps = array_column($itemsToCache, self::INDEX_EXP);

        return $exps ? max($exps) : 0;
    }

    /**
     * @throws CacheStorageException
     */
    private function getRangeIntForIp(string $ip): int
    {
        $ipInt = ip2long($ip);
        if (false === $ipInt) {
            // @codeCoverageIgnoreStart
            throw new CacheStorageException("$ip is not a valid IpV4 address");
            // @codeCoverageIgnoreEnd
        }
        try {
            $result = intdiv($ipInt, self::IPV4_BUCKET_SIZE);
            // @codeCoverageIgnoreStart
        } catch (\ArithmeticError | \DivisionByZeroError $e) {
            throw new CacheStorageException('Something went wrong during integer division: ' . $e->getMessage());
            // @codeCoverageIgnoreEnd
        }

        return $result;
    }

    /**
     * @return array|string[]
     */
    private function getTags(Decision $decision, int $bucketInt = null): array
    {
        return $bucketInt ? [self::RANGE_BUCKET_TAG] : [self::CACHE_TAG_REM, $decision->getScope()];
    }

    /**
     * @throws CacheStorageException
     */
    private function handleRangeScoped(Decision $decision, string $cachedIndex, callable $method): array
    {
        $range = $this->manageRange($decision);
        if (!$range) {
            return [self::DONE => 0, self::DEFER => 0, $cachedIndex => []];
        }
        $startAddress = $range->getStartAddress();
        $endAddress = $range->getEndAddress();

        $startInt = $this->getRangeIntForIp($startAddress->toString());
        $endInt = $this->getRangeIntForIp($endAddress->toString());
        for ($i = $startInt; $i <= $endInt; ++$i) {
            call_user_func_array($method, [$decision, $i]);
        }

        return call_user_func_array($method, [$decision]);
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function manageRange(Decision $decision): ?RangeInterface
    {
        $rangeString = $decision->getValue();
        $range = Subnet::parseString($rangeString);
        if (null === $range) {
            $this->logger->error('Range is not valid', [
                'type' => 'REM_CACHE_INVALID_RANGE',
                'decision' => $decision->toArray(),
            ]);

            return null;
        }
        $addressType = $range->getAddressType();
        if (Type::T_IPv6 === $addressType) {
            $this->logger->warning('Range for IPV6 is not implemented yet', [
                'type' => 'REM_CACHE_IPV6_RANGE_NOT_IMPLEMENTED',
                'decision' => $decision->toArray(),
            ]);

            return null;
        }

        return $range;
    }

    /**
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    private function remove(Decision $decision, int $bucketInt = null): array
    {
        $result = [self::DONE => 0, self::DEFER => 0, self::REMOVED => []];
        $cacheKey = $bucketInt ? $this->getCacheKey(self::IPV4_BUCKET_KEY, (string) $bucketInt) :
            $this->getCacheKey($decision->getScope(), $decision->getValue());
        $item = $this->getItem($cacheKey);

        if ($item->isHit()) {
            $cachedValues = $item->get();
            $indexToRemove = $this->getCachedIndex($decision->getIdentifier(), $cachedValues);
            // Early return if not in cache
            if (null === $indexToRemove) {
                return $result;
            }
            $removed = $cachedValues[$indexToRemove];
            unset($cachedValues[$indexToRemove]);
            $cachedValues = $this->cleanCachedValues($cachedValues);
            if (!$cachedValues) {
                $result[self::DONE] = (int) $this->adapter->deleteItem(base64_encode($cacheKey));
                $result[self::REMOVED] = $removed;

                return $result;
            }
            $tags = $this->getTags($decision, $bucketInt);
            $item = $this->updateDecisionItem($item, $cachedValues, $tags);
            $result[self::DEFER] = 1;
            $result[self::REMOVED] = $removed;
            if (!$this->saveDeferred($item)) {
                $this->logger->error('Saving a deferred item failed', [
                    'type' => 'REM_CACHE_STORE_DEFERRED_FAILED_FOR_REMOVE_DECISION',
                    'decision' => $decision->toArray(),
                    'bucket_int' => $bucketInt,
                ]);
                $result[self::DEFER] = 0;
                $result[self::REMOVED] = [];
            }
        }

        return $result;
    }

    /**
     * @noinspection PhpSameParameterValueInspection
     */

    /**
     * Save item; Override content if already exists.
     *
     * @throws CacheException
     * @throws InvalidArgumentException
     * @throws \Symfony\Component\Cache\Exception\InvalidArgumentException
     */
    private function saveItemWithDuration(
        string $cacheKey,
        array $content,
        int $duration,
        array $tags = []
    ): bool {
        $item = $this->getItem($cacheKey);
        $item->set($content);
        $item->expiresAt(new \DateTime("+$duration seconds"));
        if ($tags && $this->adapter instanceof TagAwareAdapterInterface) {
            $item->tag($tags);
        }

        return $this->adapter->save($item);
    }

    /**
     * @noinspection PhpSameParameterValueInspection
     */

    /**
     * @throws InvalidArgumentException
     * @throws CacheException
     * @throws \Exception
     */
    private function store(Decision $decision, int $bucketInt = null): array
    {
        $cacheKey = $bucketInt ? $this->getCacheKey(self::IPV4_BUCKET_KEY, (string) $bucketInt) :
            $this->getCacheKey($decision->getScope(), $decision->getValue());
        $item = $this->getItem($cacheKey);
        $cachedValues = $item->isHit() ? $item->get() : [];
        $indexToStore = $this->getCachedIndex($decision->getIdentifier(), $cachedValues);
        if (null !== $indexToStore) {
            return [self::DONE => 0, self::DEFER => 0, self::STORED => []];
        }
        $cachedValues = $this->cleanCachedValues($cachedValues);

        // Merge current value with cached values (if any).
        $currentValue = $this->format($decision, $bucketInt);
        $decisionsToCache = array_merge($cachedValues, [$currentValue]);
        // Rebuild cache item
        $item = $this->updateDecisionItem($item, $decisionsToCache, $this->getTags($decision, $bucketInt));

        $result = [self::DONE => 0, self::DEFER => 1, self::STORED => $currentValue];
        if (!$this->saveDeferred($item)) {
            $this->logger->error('Saving a deferred item failed', [
                'type' => 'REM_CACHE_STORE_DEFERRED_FAILED',
                'decision' => $decision->toArray(),
                'bucket_int' => $bucketInt,
            ]);
            $result[self::DEFER] = 0;
            $result[self::STORED] = [];
        }

        return $result;
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception|CacheException
     */
    private function updateDecisionItem(
        CacheItemInterface $item,
        array $valuesToCache,
        array $tags = []
    ): CacheItemInterface {
        $maxExpiration = $this->getMaxExpiration($valuesToCache);
        $item->set($valuesToCache);
        $item->expiresAt(new \DateTime('@' . $maxExpiration));
        if ($tags && $this->adapter instanceof TagAwareAdapterInterface) {
            $item->tag($tags);
        }

        return $item;
    }
}
