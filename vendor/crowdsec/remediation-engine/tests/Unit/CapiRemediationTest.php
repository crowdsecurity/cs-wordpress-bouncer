<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Tests\Unit;

/**
 * Test for capi remediation.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\CapiClient\Watcher;
use CrowdSec\RemediationEngine\AbstractRemediation as LibAbstractRemediation;
use CrowdSec\RemediationEngine\CacheStorage\AbstractCache;
use CrowdSec\RemediationEngine\CacheStorage\Memcached;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CacheStorage\Redis;
use CrowdSec\RemediationEngine\CapiRemediation;
use CrowdSec\RemediationEngine\Constants;
use CrowdSec\Common\Logger\FileLog;
use CrowdSec\RemediationEngine\Tests\Constants as TestConstants;
use CrowdSec\RemediationEngine\Tests\MockedData;
use CrowdSec\RemediationEngine\Tests\PHPUnitUtil;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
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
 * @uses \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::addGeolocationNodes
 * @uses \CrowdSec\RemediationEngine\AbstractRemediation::getCountryForIp
 * @uses \CrowdSec\RemediationEngine\AbstractRemediation::getCacheStorage
 * @uses \CrowdSec\RemediationEngine\AbstractRemediation::getIpType
 *
 * @covers \CrowdSec\RemediationEngine\Decision::getExpiresAt
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::__construct
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::handleDecisionScope
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::handleDecisionIdentifier
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::parseDurationToSeconds
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::handleDecisionExpiresAt
 * @covers \CrowdSec\RemediationEngine\CapiRemediation::__construct
 * @covers \CrowdSec\RemediationEngine\CapiRemediation::configure
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::getConfig
 * @covers \CrowdSec\RemediationEngine\CapiRemediation::getIpRemediation
 * @covers \CrowdSec\RemediationEngine\CapiRemediation::storeDecisions
 * @covers \CrowdSec\RemediationEngine\CapiRemediation::sortDecisionsByRemediationPriority
 * @covers \CrowdSec\RemediationEngine\CapiRemediation::refreshDecisions
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
 */
final class CapiRemediationTest extends AbstractRemediation
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
     * @var Watcher
     */
    private $watcher;

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
        $this->watcher = $this->getWatcherMock();

        $cachePhpfilesConfigs = ['fs_cache_path' => $this->root->url()];
        $mockedMethods = ['retrieveDecisionsForIp'];
        $this->phpFileStorage = $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
        $cacheMemcachedConfigs = [
            'memcached_dsn' => getenv('memcached_dsn') ?: 'memcached://memcached:11211',
        ];
        $this->memcachedStorage = $this->getCacheMock('MemcachedAdapter', $cacheMemcachedConfigs, $this->logger, $mockedMethods);
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
        $remediation = new CapiRemediation($remediationConfigs, $this->watcher, $this->cacheStorage, null);
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
        $this->watcher->method('getStreamDecisions')->will(
            $this->onConsecutiveCalls(
                MockedData::DECISIONS['new_ip_v4_double'], // Test 1 : new IP decision (ban) (save ok)
                MockedData::DECISIONS['new_ip_v4_other'],  // Test 2 : new IP decision (ban) (failed deferred)
                MockedData::DECISIONS['deleted_ip_v4'] // Test 3 : deleted IP decision (failed deferred)
            )
        );
        $cachePhpfilesConfigs = ['fs_cache_path' => $this->root->url()];
        $mockedMethods = [];
        $this->cacheStorage = $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
        $remediationConfigs = [];
        $remediation = new CapiRemediation($remediationConfigs, $this->watcher, $this->cacheStorage, $this->logger);

        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 2, 'deleted' => 0],
            $result,
            'Refresh count should be correct for 2 news'
        );

        // Test 2
        $mockedMethods = ['saveDeferred'];
        $this->cacheStorage = $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
        $remediationConfigs = [];
        $remediation = new CapiRemediation($remediationConfigs, $this->watcher, $this->cacheStorage, $this->logger);

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
        $this->cacheStorage = $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
        $remediationConfigs = [];
        $remediation = new CapiRemediation($remediationConfigs, $this->watcher, $this->cacheStorage, $this->logger);
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
    public function testGetIpRemediation($cacheType)
    {
        $this->setCache($cacheType);

        $remediationConfigs = ['stream_mode' => false];

        // Test with null logger
        $remediation = new CapiRemediation($remediationConfigs, $this->watcher, $this->cacheStorage, null);
        // Test is forced to stream mode
        $this->assertEquals(
            true,
            $remediation->getConfig('stream_mode'),
            'Stream mode must be true'
        );
        // Test default configs
        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $remediation->getConfig('fallback_remediation'),
            'Default fallback should be bypass'
        );
        $this->assertEquals(
            [Constants::REMEDIATION_BAN, Constants::REMEDIATION_BYPASS],
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
                ]]], // Test 2 : retrieve cached bypass
                [AbstractCache::STORED => []],  // Test 2 : retrieve empty range
                [AbstractCache::STORED => [[
                    'bypass',
                    999999999999,
                    'remediation-engine-bypass-ip-1.2.3.4',
                ]]], // Test 3 : retrieve bypass for ip
                [AbstractCache::STORED => [[
                    'ban',
                    999999999999,
                    'remediation-engine-ban-ip-1.2.3.4',
                ]]],  // Test 3 : retrieve ban for range
                [AbstractCache::STORED => [[
                    'ban',
                    311738199, //  Sunday 18 November 1979
                    'remediation-engine-ban-ip-1.2.3.4',
                ]]], // Test 4 : retrieve expired ban ip
                [AbstractCache::STORED => []]  // Test 4 : retrieve empty range
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

    public function testPrivateOrProtectedMethods()
    {
        $cachePhpfilesConfigs = ['fs_cache_path' => $this->root->url()];
        $mockedMethods = [];
        $this->cacheStorage = $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
        $remediationConfigs = [];
        $remediation = new CapiRemediation($remediationConfigs, $this->watcher, $this->cacheStorage, $this->logger);
        // convertRawDecisionsToDecisions
        // Test 1 : ok
        $rawDecisions = [
            [
                'scope' => 'IP',
                'value' => '1.2.3.4',
                'type' => 'ban',
                'origin' => 'unit',
                'duration' => '147h',
            ],
        ];
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'convertRawDecisionsToDecisions',
            [$rawDecisions]
        );

        $this->assertCount(
            1,
            $result,
            'Should return array'
        );

        $decision = $result[0];
        $this->assertEquals(
            'ban',
            $decision->getType(),
            'Should have created a correct decision'
        );
        $this->assertEquals(
            'ip',
            $decision->getScope(),
            'Should have created a normalized scope'
        );
        // Test 2: bad raw decision
        $rawDecisions = [
            [
                'value' => '1.2.3.4',
                'origin' => 'unit',
                'duration' => '147h',
            ],
        ];
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'convertRawDecisionsToDecisions',
            [$rawDecisions]
        );
        $this->assertCount(
            0,
            $result,
            'Should return empty array'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*400.*"type":"REM_RAW_DECISION_NOT_AS_EXPECTED"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Prod log content should be correct'
        );
        // Test 3 : with id
        $rawDecisions = [
            [
                'scope' => 'IP',
                'value' => '1.2.3.4',
                'type' => 'ban',
                'origin' => 'unit',
                'duration' => '147h',
                'id' => 42,
            ],
        ];
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'convertRawDecisionsToDecisions',
            [$rawDecisions]
        );

        $this->assertCount(
            1,
            $result,
            'Should return array'
        );

        $decision = $result[0];
        $this->assertEquals(
            'unit-ban-ip-1.2.3.4',
            $decision->getIdentifier(),
            'Should have created a correct decision even with id'
        );

        // comparePriorities
        $a = [
            'ban',
            1668577960,
            'CAPI-ban-range-52.3.230.0/24',
            LibAbstractRemediation::INDEX_PRIO => 0,
        ];

        $b = [
            'ban',
            1668577960,
            'CAPI-ban-range-52.3.230.0/24',
            LibAbstractRemediation::INDEX_PRIO => 0,
        ];
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'comparePriorities',
            [$a, $b]
        );

        $this->assertEquals(
            0,
            $result,
            'Should return 0 if same priority'
        );

        $a = [
            'ban',
            1668577960,
            'CAPI-ban-range-52.3.230.0/24',
            LibAbstractRemediation::INDEX_PRIO => 0,
        ];

        $b = [
            'bypass',
            1668577960,
            'CAPI-ban-range-52.3.230.0/24',
            LibAbstractRemediation::INDEX_PRIO => 1,
        ];
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'comparePriorities',
            [$a, $b]
        );

        $this->assertEquals(
            -1,
            $result,
            'Should return -1'
        );

        $result = PHPUnitUtil::callMethod(
            $remediation,
            'comparePriorities',
            [$b, $a]
        );

        $this->assertEquals(
            1,
            $result,
            'Should return 1'
        );
        // sortDecisionsByRemediationPriority
        // Test 1 : default
        $decisions = [
            [
                'bypass',
                1668577960,
                'CAPI-bypass-range-52.3.230.0/24',
            ],
            [
                'ban',
                1668577960,
                'CAPI-ban-range-52.3.230.0/24',
            ],
        ];
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'sortDecisionsByRemediationPriority',
            [$decisions]
        );
        $this->assertEquals(
            'ban',
            $result[0][0],
            'Should return highest priority (ban > bypass)'
        );
        // Test 2 : custom ordered priorities
        $remediationConfigs = [
            'ordered_remediations' => ['captcha', Constants::REMEDIATION_BAN],
            'fallback_remediation' => 'captcha',
        ];
        $remediation = new CapiRemediation($remediationConfigs, $this->watcher, $this->cacheStorage, $this->logger);
        $decisions = [
            [
                'captcha',
                1668577960,
                'CAPI-captcha-range-52.3.230.0/24',
            ],
            [
                'ban',
                1668577960,
                'CAPI-ban-range-52.3.230.0/24',
            ],
        ];
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'sortDecisionsByRemediationPriority',
            [$decisions]
        );
        $this->assertEquals(
            'captcha',
            $result[0][0],
            'Should return highest priority (captcha > ban)'
        );
        // Test 3 : fallback
        $decisions = [
            [
                'unknown',
                1668577960,
                'CAPI-unknown-range-52.3.230.0/24',
            ],
            [
                'ban',
                1668577960,
                'CAPI-ban-range-52.3.230.0/24',
            ],
        ];
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'sortDecisionsByRemediationPriority',
            [$decisions]
        );
        $this->assertEquals(
            'captcha',
            $result[0][0],
            'Should return highest priority (fallback captcha > ban)'
        );
        // Test 4 : empty
        $decisions = [];
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'sortDecisionsByRemediationPriority',
            [$decisions]
        );
        $this->assertCount(
            0,
            $result,
            'Should return empty'
        );
        // handleDecisionExpiresAt
        $remediation = $this->getMockBuilder('CrowdSec\RemediationEngine\CapiRemediation')
            ->setConstructorArgs([
                'configs' => $remediationConfigs,
                'client' => $this->watcher,
                'cacheStorage' => $this->cacheStorage,
                'logger' => $this->logger, ])
            ->onlyMethods(['getConfig'])
            ->getMock();

        $remediation->method('getConfig')
            ->will($this->returnValueMap([
                ['stream_mode', true],
                ['clean_ip_cache_duration', Constants::CACHE_EXPIRATION_FOR_CLEAN_IP],
                ['bad_ip_cache_duration', Constants::CACHE_EXPIRATION_FOR_BAD_IP],
            ]));
        // Test 1: bypass
        $type = Constants::REMEDIATION_BYPASS;
        $duration = sprintf('%ss', Constants::CACHE_EXPIRATION_FOR_CLEAN_IP);
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'handleDecisionExpiresAt',
            [$type, $duration]
        );
        $this->assertTrue(
            (time() + Constants::CACHE_EXPIRATION_FOR_CLEAN_IP) === $result,
            'Should return current time + decision duration'
        );

        // Test 2 : ban in stream mode
        $type = Constants::REMEDIATION_BAN;
        $duration = '147h';
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'handleDecisionExpiresAt',
            [$type, $duration]
        );
        $this->assertTrue(
            (time() + 147 * 60 * 60) === $result,
            'Should return current time + decision duration'
        );

        // Test 3: ban in live mode
        $remediation = $this->getMockBuilder('CrowdSec\RemediationEngine\CapiRemediation')
            ->setConstructorArgs([
                'configs' => $remediationConfigs,
                'client' => $this->watcher,
                'cacheStorage' => $this->cacheStorage,
                'logger' => $this->logger, ])
            ->onlyMethods(['getConfig'])
            ->getMock();

        $remediation->method('getConfig')
            ->will($this->returnValueMap([
                ['stream_mode', false],
                ['clean_ip_cache_duration', Constants::CACHE_EXPIRATION_FOR_CLEAN_IP],
                ['bad_ip_cache_duration', Constants::CACHE_EXPIRATION_FOR_BAD_IP],
            ]));
        $type = Constants::REMEDIATION_BAN;
        $duration = '147h';
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'handleDecisionExpiresAt',
            [$type, $duration]
        );
        $this->assertTrue(
            (time() + Constants::CACHE_EXPIRATION_FOR_BAD_IP) === $result,
            'Should return current time + bad ip duration config'
        );

        // Test 4: ban in live mode with duration < bad ip duration config
        $type = Constants::REMEDIATION_BAN;
        $duration = '15s';
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'handleDecisionExpiresAt',
            [$type, $duration]
        );
        $this->assertTrue(
            (time() + 15) === $result,
            'Should return current time + decision duration'
        );
    }

    /**
     * @dataProvider cacheTypeProvider
     */
    public function testRefreshDecisions($cacheType)
    {
        $this->setCache($cacheType);

        $remediationConfigs = [];

        $remediation = new CapiRemediation($remediationConfigs, $this->watcher, $this->cacheStorage, $this->logger);

        // Prepare next tests
        $this->watcher->method('getStreamDecisions')->will(
            $this->onConsecutiveCalls(
                MockedData::DECISIONS['new_ip_v4'],          // Test 1 : new IP decision (ban)
                MockedData::DECISIONS['new_ip_v4'],          // Test 2 : same IP decision (ban)
                MockedData::DECISIONS['deleted_ip_v4'],      // Test 3 : deleted IP decision (existing one and not)
                MockedData::DECISIONS['new_ip_v4_range'],    // Test 4 : new RANGE decision (ban)
                MockedData::DECISIONS['delete_ip_v4_range'], // Test 5 : deleted RANGE decision
                MockedData::DECISIONS['ip_v4_multiple'],     // Test 6 : retrieve multiple RANGE and IP decision
                MockedData::DECISIONS['ip_v4_multiple_bis'],  // Test 7 : retrieve multiple new and delete
                MockedData::DECISIONS['ip_v4_remove_unknown'], // Test 8 : delete unknown scope
                MockedData::DECISIONS['ip_v4_store_unknown'], // Test 9 : store unknown scope
                MockedData::DECISIONS['new_ip_v6_range'] // Test 10 : store IP V6 range
            )
        );
        // Test 1
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 1, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
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
        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . $this->prodFile),
            'Prod File should not exist'
        );
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
