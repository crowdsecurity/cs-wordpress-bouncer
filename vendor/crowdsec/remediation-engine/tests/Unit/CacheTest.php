<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Tests\Unit;

/**
 * Test for cache.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\Common\Logger\FileLog;
use CrowdSec\RemediationEngine\CacheStorage\AbstractCache;
use CrowdSec\RemediationEngine\CacheStorage\CacheStorageException;
use CrowdSec\RemediationEngine\CacheStorage\Memcached;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CacheStorage\Redis;
use CrowdSec\RemediationEngine\Constants;
use CrowdSec\RemediationEngine\Tests\Constants as TestConstants;
use CrowdSec\RemediationEngine\Tests\PHPUnitUtil;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;

/**
 * @uses \CrowdSec\RemediationEngine\Configuration\Cache\Memcached::getConfigTreeBuilder
 * @uses \CrowdSec\RemediationEngine\Configuration\Cache\PhpFiles::getConfigTreeBuilder
 * @uses \CrowdSec\RemediationEngine\Configuration\Cache\Redis::getConfigTreeBuilder
 *
 * @covers \CrowdSec\RemediationEngine\Configuration\AbstractCache::addCommonNodes
 * @covers \CrowdSec\RemediationEngine\CacheStorage\Memcached::clear
 * @covers \CrowdSec\RemediationEngine\CacheStorage\Memcached::commit
 * @covers \CrowdSec\RemediationEngine\CacheStorage\Memcached::getItem
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::__construct
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::clear
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::commit
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getAdapter
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::prune
 * @covers \CrowdSec\RemediationEngine\CacheStorage\Memcached::__construct
 * @covers \CrowdSec\RemediationEngine\CacheStorage\PhpFiles::__construct
 * @covers \CrowdSec\RemediationEngine\CacheStorage\Redis::__construct
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getConfig
 * @covers \CrowdSec\RemediationEngine\CacheStorage\Memcached::configure
 * @covers \CrowdSec\RemediationEngine\CacheStorage\PhpFiles::configure
 * @covers \CrowdSec\RemediationEngine\CacheStorage\Redis::configure
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getCacheKey
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::manageRange
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getMaxExpiration
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::cleanCachedValues
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::format
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getCachedIndex
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getItem
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getTags
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::retrieveDecisionsForIp
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::saveDeferred
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::store
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::storeDecision
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::updateDecisionItem
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::remove
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::removeDecision
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getRangeIntForIp
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::handleRangeScoped
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getIpCachedVariables
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getIpVariables
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::saveItemWithDuration
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::setIpVariables
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::retrieveDecisionsForCountry
 */
final class CacheTest extends TestCase
{
    /**
     * @var AbstractCache
     */
    private $cacheStorage;
    /**
     * @var string
     */
    private $debugFile;
    /**
     * @var FileLog
     */
    private $logger;
    /**
     * @var Memcached
     */
    private $memcachedStorage;
    /**
     * @var PhpFiles
     */
    private $phpFileStorage;
    /**
     * @var PhpFiles
     */
    private $phpFileStorageWithTags;
    /**
     * @var string
     */
    private $prodFile;
    /**
     * @var Redis
     */
    private $redisStorage;
    /**
     * @var Redis
     */
    private $redisStorageWithTags;
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    public function cacheTypeProvider(): array
    {
        return [
            'PhpFilesAdapter' => ['PhpFilesAdapter'],
            'RedisAdapter' => ['RedisAdapter'],
            'MemcachedAdapter' => ['MemcachedAdapter'],
            'PhpFilesAdapterWithTags' => ['PhpFilesAdapterWithTags'],
            'RedisAdapterWithTags' => ['RedisAdapterWithTags'],
        ];
    }

    public function setUp(): void
    {
        $this->root = vfsStream::setup(TestConstants::TMP_DIR);
        $currentDate = date('Y-m-d');
        $this->debugFile = 'debug-' . $currentDate . '.log';
        $this->prodFile = 'prod-' . $currentDate . '.log';
        $this->logger = new FileLog(['log_directory_path' => $this->root->url(), 'debug_mode' => true]);

        $cachePhpfilesConfigs = ['fs_cache_path' => $this->root->url()];
        $this->phpFileStorage = new PhpFiles($cachePhpfilesConfigs, $this->logger);
        $this->phpFileStorageWithTags = new PhpFiles(array_merge($cachePhpfilesConfigs, ['use_cache_tags' => true]), $this->logger);
        $cacheMemcachedConfigs = [
            'memcached_dsn' => getenv('memcached_dsn') ?: 'memcached://memcached:11211',
        ];
        $this->memcachedStorage = new Memcached($cacheMemcachedConfigs, $this->logger);
        $cacheRedisConfigs = [
            'redis_dsn' => getenv('redis_dsn') ?: 'redis://redis:6379',
        ];
        $this->redisStorage = new Redis($cacheRedisConfigs, $this->logger);
        $this->redisStorageWithTags = new Redis(array_merge($cacheRedisConfigs, ['use_cache_tags' => true]),
            $this->logger);
    }

    /**
     * @dataProvider cacheTypeProvider
     */
    public function testCache($cacheType)
    {
        $this->setCache($cacheType);

        switch ($cacheType) {
            case 'PhpFilesAdapterWithTags':
                $this->assertEquals(
                    'Symfony\Component\Cache\Adapter\TagAwareAdapter',
                    get_class($this->cacheStorage->getAdapter()),
                    'Adapter should be as expected'
                );
                $this->assertEquals(
                    $this->root->url(),
                    $this->cacheStorage->getConfig('fs_cache_path'),
                    'Should get config'
                );
                $this->assertNull(
                    $this->cacheStorage->getConfig('redis_dsn'),
                    'Should get null config'
                );
                $this->assertNull(
                    $this->cacheStorage->getConfig('memcached_dsn'),
                    'Should get null config'
                );
                $this->assertTrue(
                    $this->cacheStorage->getConfig('use_cache_tags')
                );
                break;
            case 'PhpFilesAdapter':
                $this->assertEquals(
                    'Symfony\Component\Cache\Adapter\PhpFilesAdapter',
                    get_class($this->cacheStorage->getAdapter()),
                    'Adapter should be as expected'
                );
                $this->assertEquals(
                    $this->root->url(),
                    $this->cacheStorage->getConfig('fs_cache_path'),
                    'Should get config'
                );
                $this->assertNull(
                    $this->cacheStorage->getConfig('redis_dsn'),
                    'Should get null config'
                );
                $this->assertNull(
                    $this->cacheStorage->getConfig('memcached_dsn'),
                    'Should get null config'
                );
                $this->assertFalse(
                    $this->cacheStorage->getConfig('use_cache_tags')
                );
                break;
            case 'RedisAdapterWithTags':
                $this->assertEquals(
                    'Symfony\Component\Cache\Adapter\RedisTagAwareAdapter',
                    get_class($this->cacheStorage->getAdapter()),
                    'Adapter should be as expected'
                );
                $this->assertNull(
                    $this->cacheStorage->getConfig('fs_cache_path'),
                    'Should get null config'
                );
                $this->assertNull(
                    $this->cacheStorage->getConfig('memcached_dsn'),
                    'Should get null config'
                );
                $this->assertNotEmpty(
                    $this->cacheStorage->getConfig('redis_dsn'),
                    'Should get config'
                );
                $this->assertTrue(
                    $this->cacheStorage->getConfig('use_cache_tags')
                );
                break;
            case 'RedisAdapter':
                $this->assertEquals(
                    'Symfony\Component\Cache\Adapter\RedisAdapter',
                    get_class($this->cacheStorage->getAdapter()),
                    'Adapter should be as expected'
                );
                $this->assertNull(
                    $this->cacheStorage->getConfig('fs_cache_path'),
                    'Should get null config'
                );
                $this->assertNull(
                    $this->cacheStorage->getConfig('memcached_dsn'),
                    'Should get null config'
                );
                $this->assertNotEmpty(
                    $this->cacheStorage->getConfig('redis_dsn'),
                    'Should get config'
                );
                $this->assertFalse(
                    $this->cacheStorage->getConfig('use_cache_tags')
                );
                break;
            case 'MemcachedAdapter':
                $this->assertEquals(
                    'Symfony\Component\Cache\Adapter\MemcachedAdapter',
                    get_class($this->cacheStorage->getAdapter()),
                    'Adapter should be as expected'
                );
                $this->assertNull(
                    $this->cacheStorage->getConfig('fs_cache_path'),
                    'Should get null config'
                );
                $this->assertNull(
                    $this->cacheStorage->getConfig('redis_dsn'),
                    'Should get null config'
                );
                $this->assertNotEmpty(
                    $this->cacheStorage->getConfig('memcached_dsn'),
                    'Should get config'
                );
                $this->assertNull(
                    $this->cacheStorage->getConfig('use_cache_tags')
                );
                break;
            default:
                throw new \Exception('Unknown $type:' . $cacheType);
        }

        $result = $this->cacheStorage->commit();
        $this->assertEquals(
            true,
            $result,
            'Commit should be ok'
        );

        $result = $this->cacheStorage->clear();
        $this->assertEquals(
            true,
            $result,
            'Cache should be clearable'
        );

        $result = $this->cacheStorage->getItem(AbstractCache::CONFIG);
        $this->assertTrue(
            $result instanceof CacheItemInterface
        );

        $error = '';
        try {
            $this->cacheStorage->prune();
        } catch (CacheStorageException $e) {
            $error = $e->getMessage();
        }
        if (in_array($cacheType, ['PhpFilesAdapter', 'PhpFilesAdapterWithTags'])) {
            $this->assertEquals(
                '',
                $error,
                'Php files Cache can be pruned'
            );
        } else {
            PHPUnitUtil::assertRegExp(
                $this,
                '/can not be pruned/',
                $error,
                'Should throw error if try to prune'
            );
        }
        // cleanCachedValues
        $cachedValues = [
            [
                'ban',
                911125444, //  Sunday 15 November 1998 10:24:04 (expired)
                'CAPI-ban-range-52.3.230.0/24',
            ],
            [
                'ban',
                5897183044, //  Monday 15 November 2156 10:24:04 (not expired)
                'CAPI-ban-range-52.3.230.0/24',
            ],
        ];
        $result = $this->cacheStorage->cleanCachedValues($cachedValues);
        $this->assertEquals(
            ['0' => [
                'ban',
                5897183044,
                'CAPI-ban-range-52.3.230.0/24',
            ]],
            $result,
            'Should return correct maximum in a re-indexed array'
        );
    }

    public function testCacheKey()
    {
        // Test also null logger
        $cachePhpfilesConfigs = ['fs_cache_path' => $this->root->url()];
        $this->phpFileStorage = new PhpFiles($cachePhpfilesConfigs);
        $this->setCache('PhpFilesAdapter');

        $cacheKey = $this->cacheStorage->getCacheKey('ip', '1.2.3.4');

        $this->assertEquals(
            'ip_1.2.3.4',
            $cacheKey,
            'Should format cache key'
        );

        $error = '';
        try {
            $this->cacheStorage->getCacheKey('Dummy', '1.2.3.4');
        } catch (CacheStorageException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '//',
            $error,
            'Should not throw error if unknown scope'
        );

        $cacheKey = $this->cacheStorage->getCacheKey('range', '1.2.3.4/24');
        $this->assertEquals(
            'range_1.2.3.4_24',
            $cacheKey,
            'Should format cache key'
        );

        $cacheKey = $this->cacheStorage->getCacheKey('ip', '1111::2222::3333@4=');
        $this->assertEquals(
            'ip_1111__2222__3333_4_',
            $cacheKey,
            'Should format cache key'
        );
    }

    public function testPrivateOrProtectedMethods()
    {
        $this->setCache('PhpFilesAdapter');

        // manageRange
        $decision = $this->getMockBuilder('CrowdSec\RemediationEngine\Decision')
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue', 'toArray'])
            ->getMock();
        $decision->method('getValue')->will(
            $this->onConsecutiveCalls(
                '1.2.3.4', // Test 1 : failed because not a range
                '2001:0db8:85a3:0000:0000:8a2e:0370:7334/24', // Test 2 IP v6 range not implemented
                '1.2.3.4/24' // Test 3 :ok
            )
        );
        // Test 1
        $result = PHPUnitUtil::callMethod(
            $this->cacheStorage,
            'manageRange',
            [$decision]
        );
        $this->assertEquals(
            null,
            $result,
            'Should return null for an IP with no range'
        );
        PHPUnitUtil::assertRegExp(
            $this,
            '/.*400.*"type":"REM_CACHE_INVALID_RANGE"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Prod log content should be correct'
        );
        // Test 2
        $result = PHPUnitUtil::callMethod(
            $this->cacheStorage,
            'manageRange',
            [$decision]
        );
        $this->assertEquals(
            null,
            $result,
            'Should return null for an IP V6'
        );
        PHPUnitUtil::assertRegExp(
            $this,
            '/.*300.*"type":"REM_CACHE_IPV6_RANGE_NOT_IMPLEMENTED"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Prod log content should be correct'
        );
        // Test 3
        $result = PHPUnitUtil::callMethod(
            $this->cacheStorage,
            'manageRange',
            [$decision]
        );
        $this->assertEquals(
            'IPLib\Range\Subnet',
            get_class($result),
            'Should return correct range'
        );
        // getMaxExpiration
        $itemsToCache = [
            [
                'ban',
                1668577960,
                'CAPI-ban-range-52.3.230.0/24',
                0,
            ],
            [
                'ban',
                1668577970,
                'CAPI-ban-range-52.3.230.0/24',
                0,
            ],
        ];
        $result = PHPUnitUtil::callMethod(
            $this->cacheStorage,
            'getMaxExpiration',
            [$itemsToCache]
        );
        $this->assertEquals(
            1668577970,
            $result,
            'Should return correct maximum'
        );
    }

    public function testRetrieveUnknownScope()
    {
        $this->setCache('PhpFilesAdapter');
        $result = $this->cacheStorage->retrieveDecisionsForIp('UNDEFINED', TestConstants::IP_V4);
        $this->assertCount(
            0,
            $result[AbstractCache::STORED],
            'Should return empty array'
        );
        PHPUnitUtil::assertRegExp(
            $this,
            '/.*300.*"type":"REM_CACHE_RETRIEVE_FOR_IP_NON_IMPLEMENTED_SCOPE"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Prod log content should be correct'
        );
    }

    /**
     * @dataProvider cacheTypeProvider
     */
    public function testIpVariableSetterAndGetter($cacheType)
    {
        $this->setCache($cacheType);
        $this->cacheStorage->setIpVariables(AbstractCache::GEOLOCATION,
            ['crowdsec_geolocation_country' => 'FR'],
            TestConstants::IP_FRANCE,
            TestConstants::CACHE_DURATION,
            [AbstractCache::GEOLOCATION]
        );

        $adapter = $this->cacheStorage->getAdapter();
        $item = $adapter->getItem(base64_encode(AbstractCache::GEOLOCATION . AbstractCache::SEP . TestConstants::IP_FRANCE));
        $this->assertEquals(
            true,
            $item->isHit(),
            'IP variable for geolocation should have been cached'
        );
        $this->assertEquals(
            ['crowdsec_geolocation_country' => 'FR'],
            $item->get(),
            'Cached item content should be correct'
        );

        $ipVariables = $this->cacheStorage->getIpVariables(AbstractCache::GEOLOCATION,
            ['crowdsec_geolocation_country', 'unknown_variable'], TestConstants::IP_FRANCE);

        $this->assertEquals(
            ['crowdsec_geolocation_country' => 'FR', 'unknown_variable' => null],
            $ipVariables,
            'Get ip variable should return cached value or null'
        );
    }

    public function testStoreAndRemoveAndRetrieveDecisionsForIpScope()
    {
        $this->setCache('PhpFilesAdapter');

        $decision = $this->getMockBuilder('CrowdSec\RemediationEngine\Decision')
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue', 'getType', 'getExpiresAt', 'getScope', 'getIdentifier', 'getOrigin'])
            ->getMock();
        $decision->method('getValue')->will(
            $this->returnValue(
                TestConstants::IP_V4
            )
        );
        $decision->method('getType')->will(
            $this->returnValue(
                Constants::REMEDIATION_BAN
            )
        );
        $decision->method('getExpiresAt')->will(
            $this->returnValue(
                4824410199
            )
        );
        $decision->method('getScope')->will(
            $this->returnValue(
                Constants::SCOPE_IP
            )
        );
        $decision->method('getIdentifier')->will(
            $this->returnValue(
                'testip'
            )
        );
        $decision->method('getOrigin')->will(
            $this->returnValue(
                'capi'
            )
        );
        // Test 1 : retrieve stored IP
        $this->cacheStorage->storeDecision($decision);

        $result = $this->cacheStorage->retrieveDecisionsForIp(Constants::SCOPE_IP, TestConstants::IP_V4);
        $this->assertCount(
            1,
            $result[AbstractCache::STORED],
            'Should get stored decisions'
        );
        $this->assertEquals(
            Constants::REMEDIATION_BAN,
            $result[AbstractCache::STORED][0][0],
            'Should get stored decisions'
        );

        // Test 2 : retrieve unstored IP
        $this->cacheStorage->removeDecision($decision);
        $result = $this->cacheStorage->retrieveDecisionsForIp(Constants::SCOPE_IP, TestConstants::IP_V4);
        $this->assertCount(
            0,
            $result[AbstractCache::STORED],
            'Should get unstored decisions'
        );
    }

    public function testStoreAndRemoveAndRetrieveDecisionsForRangeScope()
    {
        $this->setCache('PhpFilesAdapter');

        $decision = $this->getMockBuilder('CrowdSec\RemediationEngine\Decision')
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue', 'getType', 'getExpiresAt', 'getScope', 'getIdentifier', 'getOrigin'])
            ->getMock();
        $decision->method('getValue')->will(
            $this->returnValue(
                TestConstants::IP_V4 . '/' . TestConstants::IP_RANGE
            )
        );
        $decision->method('getType')->will(
            $this->returnValue(
                Constants::REMEDIATION_BAN
            )
        );
        $decision->method('getExpiresAt')->will(
            $this->returnValue(
                4824410199
            )
        );
        $decision->method('getScope')->will(
            $this->returnValue(
                Constants::SCOPE_RANGE
            )
        );
        $decision->method('getIdentifier')->will(
            $this->returnValue(
                'testrange'
            )
        );
        $decision->method('getOrigin')->will(
            $this->returnValue(
                'capi'
            )
        );
        // Test 1 : retrieve stored Range
        $this->cacheStorage->storeDecision($decision);

        $result = $this->cacheStorage->retrieveDecisionsForIp(Constants::SCOPE_RANGE, TestConstants::IP_V4);
        $this->assertCount(
            1,
            $result[AbstractCache::STORED],
            'Should get stored decisions'
        );
        $this->assertEquals(
            Constants::REMEDIATION_BAN,
            $result[AbstractCache::STORED][0][0],
            'Should get stored decisions'
        );

        // Test 2 : retrieve unstored Range
        $this->cacheStorage->removeDecision($decision);
        $result = $this->cacheStorage->retrieveDecisionsForIp(Constants::SCOPE_RANGE, TestConstants::IP_V4);
        $this->assertCount(
            0,
            $result[AbstractCache::STORED],
            'Should get unstored decisions'
        );
    }

    public function testStoreAndRemoveAndRetrieveDecisionsForCountryScope()
    {
        $this->setCache('PhpFilesAdapter');

        $decision = $this->getMockBuilder('CrowdSec\RemediationEngine\Decision')
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue', 'getType', 'getExpiresAt', 'getScope', 'getIdentifier', 'getOrigin'])
            ->getMock();
        $decision->method('getValue')->will(
            $this->returnValue(
                'FR'
            )
        );
        $decision->method('getType')->will(
            $this->returnValue(
                Constants::REMEDIATION_BAN
            )
        );
        $decision->method('getExpiresAt')->will(
            $this->returnValue(
                4824410199
            )
        );
        $decision->method('getScope')->will(
            $this->returnValue(
                Constants::SCOPE_COUNTRY
            )
        );
        $decision->method('getIdentifier')->will(
            $this->returnValue(
                'test-country'
            )
        );
        $decision->method('getOrigin')->will(
            $this->returnValue(
                'capi'
            )
        );
        // Test 1 : retrieve stored country
        $this->cacheStorage->storeDecision($decision);

        $result = $this->cacheStorage->retrieveDecisionsForCountry('FR');
        $this->assertCount(
            1,
            $result[AbstractCache::STORED],
            'Should get stored decisions'
        );
        $this->assertEquals(
            Constants::REMEDIATION_BAN,
            $result[AbstractCache::STORED][0][0],
            'Should get stored decisions'
        );

        // Test 2 : retrieve unstored IP
        $this->cacheStorage->removeDecision($decision);
        $result = $this->cacheStorage->retrieveDecisionsForCountry('FR');
        $this->assertCount(
            0,
            $result[AbstractCache::STORED],
            'Should get unstored decisions'
        );
    }

    protected function tearDown(): void
    {
        $this->cacheStorage->clear();
    }

    private function setCache(string $type)
    {
        switch ($type) {
            case 'PhpFilesAdapter':
                $this->cacheStorage = $this->phpFileStorage;
                break;
            case 'PhpFilesAdapterWithTags':
                $this->cacheStorage = $this->phpFileStorageWithTags;
                break;
            case 'RedisAdapterWithTags':
                $this->cacheStorage = $this->redisStorageWithTags;
                break;
            case 'RedisAdapter':
                $this->cacheStorage = $this->redisStorage;
                break;
            case 'MemcachedAdapter':
                $this->cacheStorage = $this->memcachedStorage;
                break;
            default:
                throw new \Exception('Unknown $type:' . $type);
        }
    }
}
