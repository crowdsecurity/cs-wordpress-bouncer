<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Tests\Unit;

/**
 * Test for lapi remediation.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\LapiClient\Bouncer;
use CrowdSec\RemediationEngine\CacheStorage\AbstractCache;
use CrowdSec\RemediationEngine\CacheStorage\Memcached;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CacheStorage\Redis;
use CrowdSec\RemediationEngine\Constants;
use CrowdSec\RemediationEngine\Constants as RemConstants;
use CrowdSec\RemediationEngine\LapiRemediation;
use CrowdSec\Common\Logger\FileLog;
use CrowdSec\RemediationEngine\Tests\Constants as TestConstants;
use CrowdSec\RemediationEngine\Tests\MockedData;
use CrowdSec\RemediationEngine\Tests\PHPUnitUtil;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 *
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::__construct
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::cleanCachedValues
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getAdapter
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getMaxExpiration
 * @uses \CrowdSec\RemediationEngine\CacheStorage\Memcached::__construct
 * @uses \CrowdSec\RemediationEngine\CacheStorage\Memcached::clear
 * @uses \CrowdSec\RemediationEngine\CacheStorage\Memcached::commit
 * @uses \CrowdSec\RemediationEngine\CacheStorage\Memcached::configure
 * @uses \CrowdSec\RemediationEngine\CacheStorage\PhpFiles::__construct
 * @uses \CrowdSec\RemediationEngine\CacheStorage\PhpFiles::configure
 * @uses \CrowdSec\RemediationEngine\CacheStorage\Redis::__construct
 * @uses \CrowdSec\RemediationEngine\CacheStorage\Redis::configure
 * @uses \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::addCommonNodes
 * @uses \CrowdSec\RemediationEngine\Configuration\Cache\Memcached::getConfigTreeBuilder
 * @uses \CrowdSec\RemediationEngine\Configuration\Cache\PhpFiles::getConfigTreeBuilder
 * @uses \CrowdSec\RemediationEngine\Configuration\Cache\Redis::getConfigTreeBuilder
 * @uses \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::validateCommon
 * @uses \CrowdSec\RemediationEngine\Decision::getOrigin
 * @uses \CrowdSec\RemediationEngine\Decision::toArray
 * @uses \CrowdSec\RemediationEngine\Configuration\Lapi::getConfigTreeBuilder
 * @uses \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::addGeolocationNodes
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getIpCachedVariables
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getIpVariables
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::saveCacheItem
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::setIpVariables
 * @uses \CrowdSec\RemediationEngine\Geolocation::__construct
 * @uses \CrowdSec\RemediationEngine\Geolocation::getMaxMindCountryResult
 * @uses \CrowdSec\RemediationEngine\Geolocation::handleCountryResultForIp
 *
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::getCacheStorage
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::handleIpV6RangeDecisions
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::getIpType
 * @covers \CrowdSec\RemediationEngine\Decision::setScope
 * @covers \CrowdSec\RemediationEngine\Decision::setValue
 * @covers \CrowdSec\RemediationEngine\Decision::getExpiresAt
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::__construct
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::handleDecisionScope
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::handleDecisionIdentifier
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::parseDurationToSeconds
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::handleDecisionExpiresAt
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::__construct
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::configure
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::getConfig
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::getIpRemediation
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::storeDecisions
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::sortDecisionsByRemediationPriority
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::refreshDecisions
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::getStreamDecisions
 * @covers \CrowdSec\RemediationEngine\Configuration\Capi::getConfigTreeBuilder
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::removeDecisions
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::clear
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::commit
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::format
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getCacheKey
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getCachedIndex
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getRangeIntForIp
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::handleRangeScoped
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::remove
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::removeDecision
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::store
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::storeDecision
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::updateCacheItem
 * @covers \CrowdSec\RemediationEngine\Decision::__construct
 * @covers \CrowdSec\RemediationEngine\Decision::getIdentifier
 * @covers \CrowdSec\RemediationEngine\Decision::getScope
 * @covers \CrowdSec\RemediationEngine\Decision::getType
 * @covers \CrowdSec\RemediationEngine\Decision::getValue
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::comparePriorities
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::manageRange
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::saveDeferred
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getTags
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getItem
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::retrieveDecisionsForIp
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::convertRawDecision
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::convertRawDecisionsToDecisions
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::validateRawDecision
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::clearCache
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::pruneCache
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::prune
 * @covers \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::getDefaultOrderedRemediations
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::getAllCachedDecisions
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::getRemediationFromDecisions
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::getCountryForIp
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::updateItem
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::getScopes
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::isWarm
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::warmUp
 *
 */
final class LapiRemediationTest extends AbstractRemediation
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
     * @var string
     */
    private $prodFile;
    /**
     * @var Redis
     */
    private $redisStorage;
    /**
     * @var vfsStreamDirectory
     */
    private $root;
    /**
     * @var Bouncer
     */
    private $bouncer;

    public function cacheTypeProvider(): array
    {
        return [
            'PhpFilesAdapter' => ['PhpFilesAdapter'],
            'RedisAdapter' => ['RedisAdapter'],
            'MemcachedAdapter' => ['MemcachedAdapter'],
        ];
    }

    /**
     * set up test environment.
     */
    public function setUp(): void
    {
        $this->root = vfsStream::setup(TestConstants::TMP_DIR);
        $currentDate = date('Y-m-d');
        $this->debugFile = 'debug-' . $currentDate . '.log';
        $this->prodFile = 'prod-' . $currentDate . '.log';
        $this->logger = new FileLog(['log_directory_path' => $this->root->url(), 'debug_mode' => true]);
        $this->bouncer = $this->getBouncerMock();

        $cachePhpfilesConfigs = ['fs_cache_path' => $this->root->url()];
        $mockedMethods = ['retrieveDecisionsForIp', 'retrieveDecisionsForCountry'];
        $this->phpFileStorage =
            $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
        $cacheMemcachedConfigs = [
            'memcached_dsn' => getenv('memcached_dsn') ?: 'memcached://memcached:11211',
        ];
        $this->memcachedStorage =
            $this->getCacheMock('MemcachedAdapter', $cacheMemcachedConfigs, $this->logger, $mockedMethods);
        $cacheRedisConfigs = [
            'redis_dsn' => getenv('redis_dsn') ?: 'redis://redis:6379',
        ];
        $this->redisStorage = $this->getCacheMock('RedisAdapter', $cacheRedisConfigs, $this->logger, $mockedMethods);
    }

    /**
     * @dataProvider cacheTypeProvider
     */
    public function testCacheActions($cacheType)
    {
        $this->setCache($cacheType);
        $remediationConfigs = [];
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, null);
        $result = $remediation->clearCache();
        $this->assertEquals(
            true,
            $result,
            'Should clear cache'
        );

        if ('PhpFilesAdapter' === $cacheType) {
            $result = $remediation->pruneCache();
            $this->assertEquals(
                true,
                $result,
                'Should prune cache'
            );
        }
    }

    public function testFailedDeferred()
    {
        // Test failed deferred
        $this->bouncer->method('getStreamDecisions')->will(
            $this->onConsecutiveCalls(
                MockedData::DECISIONS['new_ip_v4_double'], // Test 1 : new IP decision (ban) (save ok)
                MockedData::DECISIONS['new_ip_v4_other'],  // Test 2 : new IP decision (ban) (failed deferred)
                MockedData::DECISIONS['deleted_ip_v4']     // Test 3 : deleted IP decision (failed deferred)
            )
        );
        $cachePhpfilesConfigs = ['fs_cache_path' => $this->root->url()];
        $mockedMethods = [];
        $this->cacheStorage =
            $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
        $remediationConfigs = [];
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, $this->logger);

        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 2, 'deleted' => 0],
            $result,
            'Refresh count should be correct for 2 news'
        );

        // Test 2
        $mockedMethods = ['saveDeferred'];
        $this->cacheStorage =
            $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
        $remediationConfigs = [];
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, $this->logger);

        $this->cacheStorage->method('saveDeferred')->will(
            $this->onConsecutiveCalls(
                false
            )
        );
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 0, 'deleted' => 0],
            $result,
            'Refresh count should be correct for failed deferred store'
        );
        // Test 3
        $mockedMethods = ['saveDeferred'];
        $this->cacheStorage =
            $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
        $remediationConfigs = [];
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, $this->logger);
        $this->cacheStorage->method('saveDeferred')->will(
            $this->onConsecutiveCalls(
                false
            )
        );
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 0, 'deleted' => 0],
            $result,
            'Refresh count should be correct for failed deferred remove'
        );
    }

    /**
     * @dataProvider cacheTypeProvider
     */
    public function testGetIpRemediationInStreamMode($cacheType)
    {
        $this->setCache($cacheType);

        $remediationConfigs = ['stream_mode' => true];

        // Test with null logger
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, null);
        // Test stream mode value
        $this->assertEquals(
            true,
            $remediation->getConfig('stream_mode'),
            'Stream mode should be true'
        );
        // Test default configs
        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $remediation->getConfig('fallback_remediation'),
            'Default fallback should be bypass'
        );
        $this->assertEquals(
            [Constants::REMEDIATION_BAN, Constants::REMEDIATION_CAPTCHA, Constants::REMEDIATION_BYPASS],
            $remediation->getConfig('ordered_remediations'),
            'Default ordered remediation should be as expected'
        );
        // Prepare next tests
        $this->cacheStorage->method('retrieveDecisionsForIp')->will(
            $this->onConsecutiveCalls(
                [AbstractCache::STORED => []],  // Test 1 : retrieve empty IP decisions
                [AbstractCache::STORED => []],  // Test 1 : retrieve empty range decisions
                [AbstractCache::STORED => [[
                    'bypass',
                    999999999999,
                    'remediation-engine-bypass-ip-1.2.3.4',
                ]]],                            // Test 2 : retrieve cached bypass
                [AbstractCache::STORED => []],  // Test 2 : retrieve empty range
                [AbstractCache::STORED => [[
                    'bypass',
                    999999999999,
                    'remediation-engine-bypass-ip-1.2.3.4',
                ]]],                            // Test 3 : retrieve bypass for ip
                [AbstractCache::STORED => [[
                    'ban',
                    999999999999,
                    'remediation-engine-ban-ip-1.2.3.4',
                ]]],                            // Test 3 : retrieve ban for range
                [AbstractCache::STORED => [[
                    'ban',
                    311738199, //  Sunday 18 November 1979
                    'remediation-engine-ban-ip-1.2.3.4',
                ]]],                            // Test 4 : retrieve expired ban ip
                [AbstractCache::STORED => []]   // Test 4 : retrieve empty range
            )
        );
        // Test 1
        $result = $remediation->getIpRemediation(TestConstants::IP_V4);
        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $result,
            'Uncached (clean) IP should return a bypass remediation'
        );

        $adapter = $this->cacheStorage->getAdapter();
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_CACHE_KEY));
        $this->assertEquals(
            false,
            $item->isHit(),
            'Remediation should not have been cached'
        );

        // Test 2
        $result = $remediation->getIpRemediation(TestConstants::IP_V4);
        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $result,
            'Cached clean IP should return a bypass remediation'
        );
        // Test 3
        $result = $remediation->getIpRemediation(TestConstants::IP_V4);
        $this->assertEquals(
            Constants::REMEDIATION_BAN,
            $result,
            'Remediations should be ordered by priority'
        );
        // Test 4
        $result = $remediation->getIpRemediation(TestConstants::IP_V4);
        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $result,
            'Expired cached remediations should have been cleaned'
        );
    }

    /**
     * @dataProvider cacheTypeProvider
     */
    public function testGetIpRemediationInLiveMode($cacheType)
    {
        $this->setCache($cacheType);

        $remediationConfigs = ['stream_mode' => false];
        // Prepare next tests
        $currentTime = time();
        $expectedCleanTime = $currentTime + Constants::CACHE_EXPIRATION_FOR_CLEAN_IP;
        $this->cacheStorage->method('retrieveDecisionsForIp')->will(
            $this->onConsecutiveCalls(
                [AbstractCache::STORED => []],  // Test 1 : retrieve empty IP decisions
                [AbstractCache::STORED => []],  // Test 1 : retrieve empty range decisions
                [AbstractCache::STORED => [[
                    'bypass',
                    $expectedCleanTime,
                    'lapi-remediation-engine-bypass-ip-1.2.3.4',
                ]]],                            // Test 2 : retrieve cached bypass
                [AbstractCache::STORED => []],  // Test 2 : retrieve empty range
                [AbstractCache::STORED => []],  // Test 3 : retrieve empty IP decisions
                [AbstractCache::STORED => []],  // Test 3 : retrieve empty range decisions
                [AbstractCache::STORED => []],  // Test 4 : retrieve empty IP decisions
                [AbstractCache::STORED => []]   // Test 4 : retrieve empty range decisions
            )
        );
        $this->bouncer->method('getFilteredDecisions')->will(
            $this->onConsecutiveCalls(
                [],  // Test 1 : retrieve empty IP decisions
                [
                    [
                        'scope' => 'ip',
                        'value' => TestConstants::IP_V4,
                        'type' => 'captcha',
                        'origin' => 'lapi',
                        'duration' => '1h',
                    ],
                    [
                        'scope' => 'ip',
                        'value' => TestConstants::IP_V4,
                        'type' => 'ban',
                        'origin' => 'lapi',
                        'duration' => '1h',
                    ],
                ],    // Test 3
                [
                    [
                        'scope' => 'range',
                        'value' => TestConstants::IP_V6 . '/24',
                        'type' => 'ban',
                        'origin' => 'lapi',
                        'duration' => '1h',
                    ],
                ]   // Test 4 : IPv6 range scoped
            )
        );

        // Test with null logger
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, null);
        // Test stream mode value
        $this->assertEquals(
            false,
            $remediation->getConfig('stream_mode'),
            'Stream mode should be false'
        );
        // Test default configs
        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $remediation->getConfig('fallback_remediation'),
            'Default fallback should be bypass'
        );
        $this->assertEquals(
            [Constants::REMEDIATION_BAN, Constants::REMEDIATION_CAPTCHA, Constants::REMEDIATION_BYPASS],
            $remediation->getConfig('ordered_remediations'),
            'Default ordered remediation should be as expected'
        );

        // Direct LAPI call will be done only if there is no cached decisions (Test1, Test 3)
        $this->bouncer->expects($this->exactly(3))->method('getFilteredDecisions');

        // Test 1 (No cached items and no active decision)
        $result = $remediation->getIpRemediation(TestConstants::IP_V4);

        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $result,
            'Uncached (clean) and with no active decision should return a bypass remediation'
        );

        $adapter = $this->cacheStorage->getAdapter();
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_CACHE_KEY));
        $this->assertEquals(
            true,
            $item->isHit(),
            'Remediation should have been cached'
        );
        $cachedItem = $item->get();
        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $cachedItem[0][AbstractCache::INDEX_MAIN],
            'Bypass should have been cached'
        );
        $this->assertTrue(
            $expectedCleanTime === $cachedItem[0][AbstractCache::INDEX_EXP],
            'Should return current time + clean ip duration config'
        );
        $this->assertEquals(
            'lapi-remediation-engine-bypass-ip-1.2.3.4',
            $cachedItem[0][AbstractCache::INDEX_ID],
            'Should return correct indentifier'
        );
        // Test 2 (cached decisions)
        $result = $remediation->getIpRemediation(TestConstants::IP_V4);
        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $result,
            'Cached (clean) should return a bypass remediation'
        );
        // Test 3 (no cached decision and 2 actives IP decisions)
        $this->cacheStorage->clear();
        $result = $remediation->getIpRemediation(TestConstants::IP_V4);
        $this->assertEquals(
            Constants::REMEDIATION_BAN,
            $result,
            'Should return a ban remediation'
        );
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_CACHE_KEY));
        $cachedItem = $item->get();
        $this->assertCount(2, $cachedItem, 'Should have cache 2 decisions for IP');
        // Test 4 (no cached decision and 1 active IPv6 range decision)
        $this->cacheStorage->clear();
        $result = $remediation->getIpRemediation(TestConstants::IP_V6);
        $this->assertEquals(
            Constants::REMEDIATION_BAN,
            $result,
            'Should return a ban remediation'
        );
        $item = $adapter->getItem(base64_encode(RemConstants::SCOPE_IP . AbstractCache::SEP . TestConstants::IP_V6_CACHE_KEY));
        $cachedItem = $item->get();
        $this->assertCount(1, $cachedItem, 'Should have cache 1 decisions for IP');
        $this->assertEquals($cachedItem[0][0], 'ban', 'Should be a ban');
    }

    /**
     * @dataProvider cacheTypeProvider
     */
    public function testGetIpRemediationInLiveModeWithGeolocation($cacheType)
    {
        $databasePath = __DIR__ . '/../geolocation/GeoLite2-Country.mmdb';
        if (!file_exists($databasePath)) {
            $this->fail('For this test, there must be a MaxMind Database here: ' . $databasePath);
        }
        $this->setCache($cacheType);

        $remediationConfigs = [
            'stream_mode' => false,
            'geolocation' => [
                'cache_duration' => Constants::CACHE_EXPIRATION_FOR_GEO,
                'enabled' => true,
                'type' => 'maxmind',
                'maxmind' => [
                    'database_type' => Constants::MAXMIND_COUNTRY,
                    'database_path' => $databasePath,
                ],
            ],
        ];
        // Prepare next tests
        $currentTime = time();
        $expectedBadTime = $currentTime + Constants::CACHE_EXPIRATION_FOR_BAD_IP;
        $this->cacheStorage->method('retrieveDecisionsForIp')->will(
            $this->onConsecutiveCalls(
                [AbstractCache::STORED => []],  // Test 1 : retrieve empty IP decisions
                [AbstractCache::STORED => []]  // Test 1 : retrieve empty range decisions
            )
        );
        $this->cacheStorage->method('retrieveDecisionsForCountry')->will(
            $this->onConsecutiveCalls(
                [AbstractCache::STORED => []]  // Test 1 : retrieve empty country decisions
            )
        );
        $this->bouncer->method('getFilteredDecisions')->will(
            $this->onConsecutiveCalls(
                [],  // Test 1 : retrieve empty IP decisions
                [
                    [
                        'scope' => 'country',
                        'value' => 'AU', // 1.2.3.4 is localized in AU
                        'type' => 'captcha',
                        'origin' => 'lapi',
                        'duration' => '1h',
                    ],
                ]    // Test 1 : retrieve captcha decision for country
            )
        );

        // Test with null logger
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, null);
        // Test stream mode value
        $this->assertEquals(
            false,
            $remediation->getConfig('stream_mode'),
            'Stream mode should be false'
        );

        // Direct LAPI call will be done only if there is no cached decisions (Test1 for ip, Test 1 for country)
        $this->bouncer->expects($this->exactly(2))->method('getFilteredDecisions');

        // Test 1 (No cached items and 1 active country decision)
        $result = $remediation->getIpRemediation(TestConstants::IP_V4);

        $this->assertEquals(
            Constants::REMEDIATION_CAPTCHA,
            $result,
            'Should return a captcha'
        );

        $adapter = $this->cacheStorage->getAdapter();
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_CACHE_KEY));
        $this->assertEquals(
            false,
            $item->isHit(),
            'Remediation for IP should have not been cached'
        );
        $item = $adapter->getItem(base64_encode(Constants::SCOPE_COUNTRY . AbstractCache::SEP . 'AU'));
        $this->assertEquals(
            true,
            $item->isHit(),
            'Remediation for country should have not been cached'
        );

        $cachedItem = $item->get();
        $this->assertEquals(
            Constants::REMEDIATION_CAPTCHA,
            $cachedItem[0][AbstractCache::INDEX_MAIN],
            'captcha should have been cached'
        );
        $this->assertTrue(
            $expectedBadTime === $cachedItem[0][AbstractCache::INDEX_EXP],
            'Should return current time + bad ip duration config'
        );
        $this->assertEquals(
            'lapi-captcha-country-AU',
            $cachedItem[0][AbstractCache::INDEX_ID],
            'Should return correct indentifier'
        );
    }

    /**
     * @dataProvider cacheTypeProvider
     */
    public function testGetIpRemediationInStreamModeWithGeolocation($cacheType)
    {
        $databasePath = __DIR__ . '/../geolocation/GeoLite2-Country.mmdb';
        if (!file_exists($databasePath)) {
            $this->fail('For this test, there must be a MaxMind Database here: ' . $databasePath);
        }
        $this->setCache($cacheType);

        $remediationConfigs = [
            'stream_mode' => true,
            'geolocation' => [
                'cache_duration' => Constants::CACHE_EXPIRATION_FOR_GEO,
                'enabled' => true,
                'type' => 'maxmind',
                'maxmind' => [
                    'database_type' => Constants::MAXMIND_COUNTRY,
                    'database_path' => $databasePath,
                ],
            ],
        ];
        // Prepare next tests
        $currentTime = time();
        $expectedCleanTime = $currentTime + Constants::CACHE_EXPIRATION_FOR_CLEAN_IP;
        $this->cacheStorage->method('retrieveDecisionsForIp')->will(
            $this->onConsecutiveCalls(
                [AbstractCache::STORED => []],  // Test 1 : retrieve empty IP decisions
                [AbstractCache::STORED => []],  // Test 1 : retrieve empty range decisions
                [AbstractCache::STORED => []],  // Test 2 : retrieve empty IP decisions
                [AbstractCache::STORED => []],  // Test 2 : retrieve empty range decisions
                [AbstractCache::STORED => [[
                    'ban',
                    999999999999,
                    'lapi-ban-ip-1.2.3.4',
                ]]],                            // Test 3 retrieve IP ban
                [AbstractCache::STORED => []]   // Test 3 : retrieve empty range decisions
            )
        );
        $this->cacheStorage->method('retrieveDecisionsForCountry')->will(
            $this->onConsecutiveCalls(
                [AbstractCache::STORED => []],  // Test 1 : retrieve empty Country
                [AbstractCache::STORED => [[
                    'ban',
                    999999999999,
                    'lapi-ban-country-AU',
                ]]],                            // Test 2 : retrieve ban for country
                [AbstractCache::STORED => [[
                    'captcha',
                    999999999999,
                    'lapi-captcha-country-AU',
                ]]]                             // Test 2 : retrieve ban for country
            )
        );

        // Test with null logger
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, null);

        // Test 1 (No cached items and no active decision)
        $result = $remediation->getIpRemediation(TestConstants::IP_V4);

        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $result,
            'Uncached (clean) and with no active decision should return a bypass remediation'
        );

        $adapter = $this->cacheStorage->getAdapter();
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_CACHE_KEY));
        $this->assertEquals(
            false,
            $item->isHit(),
            'Remediation should have not been cached'
        );
        // Test 2 (1 active decision for country)
        $result = $remediation->getIpRemediation(TestConstants::IP_V4);

        $this->assertEquals(
            Constants::REMEDIATION_BAN,
            $result,
            'Cached country ban should return ban'
        );

        // Test 3 (1 active decision for country (captcha) and 1 for ip (ban))
        $result = $remediation->getIpRemediation(TestConstants::IP_V4);

        $this->assertEquals(
            Constants::REMEDIATION_BAN,
            $result,
            'Should return higshest priority'
        );
    }

    /**
     * @dataProvider cacheTypeProvider
     */
    public function testRefreshDecisions($cacheType)
    {
        $this->setCache($cacheType);

        $remediationConfigs = [];

        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, $this->logger);

        // Prepare next tests
        $this->bouncer->method('getStreamDecisions')->will(
            $this->onConsecutiveCalls(
                MockedData::DECISIONS['new_ip_v4'],            // Test 1 : new IP decision (ban)
                MockedData::DECISIONS['new_ip_v4'],            // Test 2 : same IP decision (ban)
                MockedData::DECISIONS['deleted_ip_v4'],        // Test 3 : deleted IP decision (existing one and not)
                MockedData::DECISIONS['new_ip_v4_range'],      // Test 4 : new RANGE decision (ban)
                MockedData::DECISIONS['delete_ip_v4_range'],   // Test 5 : deleted RANGE decision
                MockedData::DECISIONS['ip_v4_multiple'],       // Test 6 : retrieve multiple RANGE and IP decision
                MockedData::DECISIONS['ip_v4_multiple_bis'],   // Test 7 : retrieve multiple new and delete
                MockedData::DECISIONS['ip_v4_remove_unknown'], // Test 8 : delete unknown scope
                MockedData::DECISIONS['ip_v4_store_unknown'],  // Test 9 : store unknown scope
                MockedData::DECISIONS['new_ip_v6_range'],  // Test 10 : store IP V6 range
                MockedData::DECISIONS['country_ban']       // Test 11 : store country decision
            )
        );
        $this->bouncer->expects($this->exactly(11))
            ->method('getStreamDecisions')
            ->withConsecutive(
                [true, ['scopes' => 'ip,range']],
                [false, ['scopes' => 'ip,range']],
                [false, ['scopes' => 'ip,range']],
                [false, ['scopes' => 'ip,range']],
                [false, ['scopes' => 'ip,range']],
                [false, ['scopes' => 'ip,range']],
                [false, ['scopes' => 'ip,range']],
                [false, ['scopes' => 'ip,range']],
                [false, ['scopes' => 'ip,range']],
                [false, ['scopes' => 'ip,range']],
                [false, ['scopes' => 'ip,range,country']]
            );

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . $this->prodFile),
            'Prod File should not exist'
        );
        $item = $this->cacheStorage->getItem(AbstractCache::CONFIG);
        $this->assertEquals(
            false,
            $item->isHit(),
            'Cached should not be warmed up'
        );
        // Test 1
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 1, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );
        $item = $this->cacheStorage->getItem(AbstractCache::CONFIG);
        $this->assertEquals(
            true,
            $item->isHit(),
            'Cached should be warmed up'
        );
        $this->assertEquals(
            [AbstractCache::WARMUP => true],
            $item->get(),
            'Warmup cache item should be as expected'
        );

        $adapter = $this->cacheStorage->getAdapter();
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_2_CACHE_KEY));
        $this->assertEquals(
            true,
            $item->isHit(),
            'Remediation should have been cached'
        );
        $cachedValue = $item->get();
        $this->assertEquals(
            Constants::REMEDIATION_BAN,
            $cachedValue[0][AbstractCache::INDEX_MAIN],
            'Remediation should have been cached with correct value'
        );
        // Test 2
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 0, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_2_CACHE_KEY));
        $this->assertEquals(
            true,
            $item->isHit(),
            'Remediation should still be cached'
        );
        // Test 3
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 0, 'deleted' => 1],
            $result,
            'Refresh count should be correct'
        );
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_2_CACHE_KEY));
        $this->assertEquals(
            false,
            $item->isHit(),
            'Remediation should have been deleted'
        );
        // Test 4
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 1, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );
        $item = $adapter->getItem(
            base64_encode(TestConstants::IP_V4_RANGE_CACHE_KEY)
        );
        $this->assertEquals(
            true,
            $item->isHit(),
            'Remediation should have been cached'
        );
        $item = $adapter->getItem(
            base64_encode(
                TestConstants::IP_V4_BUCKET_CACHE_KEY)
        );
        $this->assertEquals(
            true,
            $item->isHit(),
            'Range bucket should have been cached'
        );
        // Test 5
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 0, 'deleted' => 1],
            $result,
            'Refresh count should be correct'
        );
        $item = $adapter->getItem(
            base64_encode(TestConstants::IP_V4_RANGE_CACHE_KEY)
        );
        $this->assertEquals(
            false,
            $item->isHit(),
            'Remediation should have been deleted'
        );
        $item = $adapter->getItem(
            base64_encode(
                TestConstants::IP_V4_BUCKET_CACHE_KEY)
        );
        $this->assertEquals(
            false,
            $item->isHit(),
            'Range bucket should have been deleted'
        );
        // Test 6
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 5, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_CACHE_KEY));
        $cachedValue = $item->get();
        $this->assertEquals(
            2,
            count($cachedValue),
            'Should have cached 2 remediations'
        );
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_2_CACHE_KEY));
        $cachedValue = $item->get();
        $this->assertEquals(
            1,
            count($cachedValue),
            'Should have cached 1 remediation'
        );
        // Test 7
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 1, 'deleted' => 1],
            $result,
            'Refresh count should be correct'
        );
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_CACHE_KEY));
        $cachedValue = $item->get();
        $this->assertEquals(
            1,
            count($cachedValue),
            'Should stay 1 cached remediation'
        );
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_2_CACHE_KEY));
        $cachedValue = $item->get();
        $this->assertEquals(
            2,
            count($cachedValue),
            'Should now have 2 cached remediation'
        );

        // Test 8
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 0, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );
        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . $this->prodFile),
            'Prod File should  exist'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*300.*"type":"REM_CACHE_REMOVE_NON_IMPLEMENTED_SCOPE.*CAPI-ban-do-not-know-delete-1.2.3.4"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Prod log content should be correct'
        );
        // Test 9
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 0, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*300.*"type":"REM_CACHE_STORE_NON_IMPLEMENTED_SCOPE.*CAPI-ban-do-not-know-store-1.2.3.4"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Prod log content should be correct'
        );
        // Test 10
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 0, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*300.*"type":"REM_CACHE_IPV6_RANGE_NOT_IMPLEMENTED"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Prod log content should be correct'
        );
        // Test 11
        $remediationConfigs = ['geolocation' => ['enabled' => true]];
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, $this->logger);
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 1, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );
        $item = $adapter->getItem(
            base64_encode(Constants::SCOPE_COUNTRY . AbstractCache::SEP . 'FR')
        );
        $this->assertEquals(
            true,
            $item->isHit(),
            'Remediation should have been cached for country'
        );
        // Test 12 (stream mode)
        $remediationConfigs = ['stream_mode' => false];
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, $this->logger);
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 0, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );
        PHPUnitUtil::assertRegExp(
            $this,
            '/.*200.*Decisions refresh is only available in stream mode.*"type":"LAPI_REM_REFRESH_DECISIONS"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Prod log content should be correct'
        );

        // parseDurationToSeconds
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'parseDurationToSeconds',
            ['1h']
        );
        $this->assertEquals(
            3600,
            $result,
            'Should convert in seconds'
        );

        $result = PHPUnitUtil::callMethod(
            $remediation,
            'parseDurationToSeconds',
            ['147h']
        );
        $this->assertEquals(
            3600 * 147,
            $result,
            'Should convert in seconds'
        );

        $result = PHPUnitUtil::callMethod(
            $remediation,
            'parseDurationToSeconds',
            ['147h23m43s']
        );
        $this->assertEquals(
            3600 * 147 + 23 * 60 + 43,
            $result,
            'Should convert in seconds'
        );

        $result = PHPUnitUtil::callMethod(
            $remediation,
            'parseDurationToSeconds',
            ['147h23m43000.5665ms']
        );
        $this->assertEquals(
            3600 * 147 + 23 * 60 + 43,
            $result,
            'Should convert in seconds'
        );

        $result = PHPUnitUtil::callMethod(
            $remediation,
            'parseDurationToSeconds',
            ['23m43s']
        );
        $this->assertEquals(
            23 * 60 + 43,
            $result,
            'Should convert in seconds'
        );
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'parseDurationToSeconds',
            ['-23m43s']
        );
        $this->assertEquals(
            -23 * 60 - 43,
            $result,
            'Should convert in seconds'
        );

        $result = PHPUnitUtil::callMethod(
            $remediation,
            'parseDurationToSeconds',
            ['abc']
        );
        $this->assertEquals(
            0,
            $result,
            'Should return 0 on bad format'
        );
        PHPUnitUtil::assertRegExp(
            $this,
            '/.*400.*"type":"REM_DECISION_DURATION_PARSE_ERROR"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Prod log content should be correct'
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
