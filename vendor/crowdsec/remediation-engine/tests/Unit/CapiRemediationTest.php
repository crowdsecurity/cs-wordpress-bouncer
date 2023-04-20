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
use CrowdSec\Common\Exception;
use CrowdSec\Common\Logger\FileLog;
use CrowdSec\RemediationEngine\AbstractRemediation as LibAbstractRemediation;
use CrowdSec\RemediationEngine\CacheStorage\AbstractCache;
use CrowdSec\RemediationEngine\CacheStorage\Memcached;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CacheStorage\Redis;
use CrowdSec\RemediationEngine\CapiRemediation;
use CrowdSec\RemediationEngine\Constants;
use CrowdSec\RemediationEngine\Tests\Constants as TestConstants;
use CrowdSec\RemediationEngine\Tests\MockedData;
use CrowdSec\RemediationEngine\Tests\PHPUnitUtil;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::__construct
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::cleanCachedValues
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getAdapter
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getMaxExpiration
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\Memcached::__construct
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\Memcached::clear
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\Memcached::commit
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\Memcached::configure
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\PhpFiles::__construct
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\PhpFiles::configure
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\Redis::__construct
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\Redis::configure
 * @uses   \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::addCommonNodes
 * @uses   \CrowdSec\RemediationEngine\Configuration\Cache\Memcached::getConfigTreeBuilder
 * @uses   \CrowdSec\RemediationEngine\Configuration\Cache\PhpFiles::getConfigTreeBuilder
 * @uses   \CrowdSec\RemediationEngine\Configuration\Cache\Redis::getConfigTreeBuilder
 * @uses   \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::validateCommon
 * @uses   \CrowdSec\RemediationEngine\Decision::getOrigin
 * @uses   \CrowdSec\RemediationEngine\Decision::toArray
 * @uses   \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::addGeolocationNodes
 * @uses   \CrowdSec\RemediationEngine\AbstractRemediation::getCountryForIp
 * @uses \CrowdSec\RemediationEngine\Configuration\AbstractCache::addCommonNodes
 *
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::handleRemediationFromDecisions
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::sortDecisionsByPriority
 *
 * @uses \CrowdSec\RemediationEngine\AbstractRemediation::updateRemediationOriginCount
 *
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::getCacheStorage
 *
 * @uses   \CrowdSec\RemediationEngine\AbstractRemediation::getIpType
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\Memcached::getItem
 *
 * @covers   \CrowdSec\RemediationEngine\CapiRemediation::convertRawCapiDecisionsToDecisions
 * @covers   \CrowdSec\RemediationEngine\CapiRemediation::handleListDecisions
 *
 * @uses \CrowdSec\RemediationEngine\Configuration\Capi::addCapiNodes
 *
 * @covers \CrowdSec\RemediationEngine\CapiRemediation::formatIfModifiedSinceHeader
 * @covers \CrowdSec\RemediationEngine\CapiRemediation::handleListPullHeaders
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::upsertItem
 * @covers \CrowdSec\RemediationEngine\Decision::getExpiresAt
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::__construct
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::normalize
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
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::updateDecisionItem
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
 * @covers \CrowdSec\RemediationEngine\CapiRemediation::getClient
 * @covers \CrowdSec\RemediationEngine\CapiRemediation::validateBlocklist
 * @covers \CrowdSec\RemediationEngine\CapiRemediation::shouldAddModifiedSince
 * @covers \CrowdSec\RemediationEngine\CapiRemediation::handleListResponse
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
            'PhpFilesAdapterWithTags' => ['PhpFilesAdapterWithTags'],
            'RedisAdapterWithTags' => ['RedisAdapterWithTags'],
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
        $this->phpFileStorage =
            $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
        $this->phpFileStorageWithTags =
            $this->getCacheMock('PhpFilesAdapter', array_merge($cachePhpfilesConfigs, ['use_cache_tags' => true]),
                $this->logger, $mockedMethods);
        $cacheMemcachedConfigs = [
            'memcached_dsn' => getenv('memcached_dsn') ?: 'memcached://memcached:11211',
        ];
        $this->memcachedStorage =
            $this->getCacheMock('MemcachedAdapter', $cacheMemcachedConfigs, $this->logger, $mockedMethods);
        $cacheRedisConfigs = [
            'redis_dsn' => getenv('redis_dsn') ?: 'redis://redis:6379',
        ];
        $this->redisStorage = $this->getCacheMock('RedisAdapter', $cacheRedisConfigs, $this->logger, $mockedMethods);
        $this->redisStorageWithTags = $this->getCacheMock('RedisAdapter', array_merge($cacheRedisConfigs,
            ['use_cache_tags' => true]),
            $this->logger,
            $mockedMethods);
    }

    /**
     * @dataProvider cacheTypeProvider
     */
    public function testCacheActions($cacheType)
    {
        $this->setCache($cacheType);
        $remediationConfigs = [];
        $remediation = new CapiRemediation($remediationConfigs, $this->watcher, $this->cacheStorage, null);
        $this->assertEquals(
            $this->cacheStorage,
            $remediation->getCacheStorage()
        );
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
                MockedData::DECISIONS_CAPI_V3['new_ip_v4_double'], // Test 1 : new IP decision (ban) (save ok)
                MockedData::DECISIONS_CAPI_V3['new_ip_v4_other'],  // Test 2 : new IP decision (ban) (failed deferred)
                MockedData::DECISIONS_CAPI_V3['deleted_ip_v4'] // Test 3 : deleted IP decision (failed deferred)
            )
        );
        $cachePhpfilesConfigs = ['fs_cache_path' => $this->root->url()];
        $mockedMethods = [];
        $this->cacheStorage =
            $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
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
        $this->cacheStorage =
            $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
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
        $this->cacheStorage =
            $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
        $remediationConfigs = [];
        $remediation = new CapiRemediation($remediationConfigs, $this->watcher, $this->cacheStorage, $this->logger);
        $this->cacheStorage->method('saveDeferred')->will(
            $this->onConsecutiveCalls(
                false
            )
        );
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 0, 'deleted' => 1],
            $result,
            'Failed deferred is not call as there is only one decision cached : decision is deleted directly without deferring'
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
        $this->assertEquals($this->watcher, $remediation->getClient());
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
                    'clean-bypass-ip-1.2.3.4',
                    'clean',
                ]]], // Test 2 : retrieve cached bypass
                [AbstractCache::STORED => []],  // Test 2 : retrieve empty range
                [AbstractCache::STORED => [[
                    'bypass',
                    999999999999,
                    'clean-bypass-ip-1.2.3.4',
                    'clean',
                ]]], // Test 3 : retrieve bypass for ip
                [AbstractCache::STORED => [[
                    'ban',
                    999999999999,
                    'capi-ban-ip-1.2.3.4',
                    'capi',
                ]]],  // Test 3 : retrieve ban for range
                [AbstractCache::STORED => [[
                    'ban',
                    311738199, //  Sunday 18 November 1979
                    'capi-ban-ip-1.2.3.4',
                    'capi',
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
        $this->cacheStorage =
            $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
        $remediationConfigs = [];
        $remediation = new CapiRemediation($remediationConfigs, $this->watcher, $this->cacheStorage, $this->logger);
        // convertRawCapiDecisionsToDecisions
        // test 1 : single added decision
        $rawDecisions = [
            [
                'scope' => 'ip',
                'decisions' => [
                        [
                            'value' => '1.2.3.4',
                            'duration' => '147h',
                        ],
                    ],
            ],
        ];
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'convertRawCapiDecisionsToDecisions',
            [$rawDecisions]
        );

        $this->assertCount(
            1,
            $result,
            'Should return array of single decision'
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
        $this->assertEquals(
            'capi',
            $decision->getOrigin(),
            'Should have created a normalized origin'
        );

        // Test 2 : deleted decisions
        $rawDecisions = [
            [
                'scope' => 'range',
                'decisions' => [
                        '1.2.3.4/24', '5.6.7.8/24',
                    ],
            ],
        ];
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'convertRawCapiDecisionsToDecisions',
            [$rawDecisions]
        );

        $this->assertCount(
            2,
            $result,
            'Should return array of two decision'
        );

        $decision = $result[0];
        $this->assertEquals(
            'ban',
            $decision->getType(),
            'Should have created a correct decision'
        );
        $this->assertEquals(
            'range',
            $decision->getScope(),
            'Should have created a normalized scope'
        );
        $this->assertEquals(
            'capi',
            $decision->getOrigin(),
            'Should have created a normalized origin'
        );
        $this->assertEquals(
            'capi-ban-range-1.2.3.4/24',
            $decision->getIdentifier(),
            'Should have created a normalized identifier'
        );

        // validateBlocklist
        $blocklist = [
            'name' => 'tor-exit-node',
            'url' => 'https://',
            'remediation' => 'captcha',
            'scope' => 'ip',
            'duration' => '24h',
        ];

        $result = PHPUnitUtil::callMethod(
            $remediation,
            'validateBlocklist',
            [$blocklist]
        );

        $this->assertEquals(
            true,
            $result
        );
        $blocklist = [
            'name' => 'tor-exit-node',
            'scope' => 'ip',
            'duration' => '24h',
        ];
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'validateBlocklist',
            [$blocklist]
        );

        $this->assertEquals(
            false,
            $result
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
        // sortDecisionsByPriority
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
            'sortDecisionsByPriority',
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
            'sortDecisionsByPriority',
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
            'sortDecisionsByPriority',
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
            'sortDecisionsByPriority',
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

        // formatIfModifiedSinceHeader
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'formatIfModifiedSinceHeader',
            [1677821719]
        );
        $this->assertEquals(
            'Fri, 03 Mar 2023 05:35:19 GMT',
            $result,
            'Should good date and format'
        );

        // shouldAddModifiedSince
        // test 1 : cron running at 23h ; list expires at 24h, frequency 2h => should pull even if not modified
        // pull time: 1677884400 : Fri, 03 Mar 2023 23:00:00 GMT
        // list expiration time: 1677888000 : Sat, 04 Mar 2023 00:00:00 GMT
        // frequency 7200
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'shouldAddModifiedSince',
            [1677884400, 1677888000, 7200]
        );
        $this->assertEquals(
            false,
            $result
        );

        // test 2 : cron running at 21h ; list expires at 24h, frequency 2h => should NOT pull even if not modified
        // pull time: 1677877200 : Fri, 03 Mar 2023 21:00:00 GMT
        // list expiration time: 1677888000 : Sat, 04 Mar 2023 00:00:00 GMT
        // frequency 7200
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'shouldAddModifiedSince',
            [1677877200, 1677888000, 7200]
        );
        $this->assertEquals(
            true,
            $result
        );

        // test 3 (edge case) : cron running at 22h ; list expires at 24h, frequency 2h => should pull even if not
        // modified
        // pull time: 1677880800 : Fri, 03 Mar 2023 22:00:00 GMT
        // list expiration time: 1677888000 : Sat, 04 Mar 2023 00:00:00 GMT
        // frequency 7200
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'shouldAddModifiedSince',
            [1677880800, 1677888000, 7200]
        );
        $this->assertEquals(
            false,
            $result
        );

        // handleListPullHeaders
        // test 1 : cron running at 23h ; list expires at 24h (last pull at 00h), frequency 4h
        // => SHOULD pull even if not modified
        // Last pull: 1677801600 : Fri, 03 Mar 2023 00:00:00 GMT
        // pull time: 1677884400 : Fri, 03 Mar 2023 23:00:00 GMT
        // list expiration time: 1677888000 : Sat, 04 Mar 2023 00:00:00 GMT
        // frequency 14400
        $headers = [];
        $lastPullContent = [
            AbstractCache::INDEX_EXP => 1677888000,
            AbstractCache::LAST_PULL => 1677801600];
        $pullTime = 1677884400;
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'handleListPullHeaders',
            [$headers, $lastPullContent, $pullTime]
        );

        $this->assertEquals(
            [],
            $result
        );
        // test 2 : cron running at 16h ; list expires at 24h (last pull at 00h), frequency 4h
        // => SHOULD NOT pull even if not modified
        // Last pull: 1677801600 : Fri, 03 Mar 2023 00:00:00 GMT
        // pull time: 1677859200 : Fri, 03 Mar 2023 16:00:00 GMT
        // list expiration time: 1677888000 : Sat, 04 Mar 2023 00:00:00 GMT
        // frequency 14400
        $headers = [];
        $lastPullContent = [
            AbstractCache::INDEX_EXP => 1677888000,
            AbstractCache::LAST_PULL => 1677801600];
        $pullTime = 1677859200;
        $result = PHPUnitUtil::callMethod(
            $remediation,
            'handleListPullHeaders',
            [$headers, $lastPullContent, $pullTime]
        );

        $this->assertEquals(
            ['If-Modified-Since' => 'Fri, 03 Mar 2023 00:00:00 GMT'],
            $result
        );
    }

    /**
     * @dataProvider cacheTypeProvider
     */
    public function testRefreshDecisions($cacheType)
    {
        $this->setCache($cacheType);

        $remediationConfigs = [];

        $capiHandlerMock = $this->getMockBuilder('CrowdSec\CapiClient\Client\CapiHandler\Curl')
            ->disableOriginalConstructor()
            ->onlyMethods(['getListDecisions'])
            ->getMock();

        $remediation = new CapiRemediation($remediationConfigs, $this->watcher, $this->cacheStorage, $this->logger);

        // Prepare next tests
        $this->watcher->method('getStreamDecisions')->will(
            $this->onConsecutiveCalls(
                MockedData::DECISIONS_CAPI_V3['new_ip_v4'],          // Test 1 : new IP decision (ban)
                MockedData::DECISIONS_CAPI_V3['new_ip_v4'],          // Test 2 : same IP decision (ban)
                MockedData::DECISIONS_CAPI_V3['deleted_ip_v4'],      // Test 3 : deleted IP decision (existing one and not)
                MockedData::DECISIONS_CAPI_V3['new_ip_v4_range'],    // Test 4 : new RANGE decision (ban)
                MockedData::DECISIONS_CAPI_V3['delete_ip_v4_range'], // Test 5 : deleted RANGE decision
                MockedData::DECISIONS_CAPI_V3['ip_v4_multiple'],     // Test 6 : retrieve multiple RANGE and IP decision
                MockedData::DECISIONS_CAPI_V3['ip_v4_multiple_bis'],  // Test 7 : retrieve multiple new and delete
                MockedData::DECISIONS_CAPI_V3['ip_v4_remove_unknown'], // Test 8 : delete unknown scope
                MockedData::DECISIONS_CAPI_V3['ip_v4_store_unknown'], // Test 9 : store unknown scope
                MockedData::DECISIONS_CAPI_V3['new_ip_v6_range'], // Test 10 : store IP V6 range
                MockedData::DECISIONS_CAPI_V3['new_ip_v4_and_list'], // Test 11: IPv4 and list
                MockedData::DECISIONS_CAPI_V3['new_ip_v4_and_list'], // Test 12: IPv4 and list
                MockedData::DECISIONS_CAPI_V3['new_ip_v4_and_list'], // Test 13: IPv4 and list but error is thrown
                MockedData::DECISIONS_CAPI_V3['new_ip_v4_with_0_duration'] // Test 14: IPv4 and 0h duration
            )
        );
        $this->watcher->method('getCapiHandler')->will(
            $this->returnValue(
                $capiHandlerMock
            )
        );

        $capiHandlerMock->method('getListDecisions')->will(
            $this->onConsecutiveCalls(
                TestConstants::IP_V4_2, // Test 11 : new IP v4 + list
                '', // Test 12 : new IP v4 + list again (not modified)
                $this->throwException(new Exception('UNIT TEST EXCEPTION')) // Test 13 : will throw an error
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
            ['new' => 3, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_CACHE_KEY));
        $cachedValue = $item->get();
        $this->assertEquals(
            1,
            count($cachedValue),
            'Should have cached 1 remediation'
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
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_3_CACHE_KEY));
        $cachedValue = $item->get();
        $this->assertEquals(
            1,
            count($cachedValue),
            'Should stay 1 cached remediation'
        );
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_2_CACHE_KEY));
        $cachedValue = $item->get();
        $this->assertEquals(
            1,
            count($cachedValue),
            'Should now have 1 cached remediation'
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
            '/.*300.*"type":"REM_CACHE_REMOVE_NON_IMPLEMENTED_SCOPE.*capi-ban-do-not-know-delete-1.2.3.4"/',
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
            '/.*300.*"type":"REM_CACHE_STORE_NON_IMPLEMENTED_SCOPE.*capi-ban-do-not-know-store-1.2.3.4"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Prod log content should be correct'
        );
        // Test 10
        $remediation->clearCache();
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

        // Test 11 : new + list
        $result = $remediation->refreshDecisions();
        $time = time();
        $listExpiration = $time + 24 * 60 * 60;
        $this->assertEquals(
            ['new' => 2, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );

        $lastPullCacheKey = $remediation->getCacheStorage()->getCacheKey(
            AbstractCache::LIST,
            'tor-exit-nodes'
        );

        $lastPullItem = $remediation->getCacheStorage()->getItem($lastPullCacheKey);
        $this->assertEquals(
            true,
            $lastPullItem->isHit()
        );
        $lastPullItemContent = $lastPullItem->get();
        // Avoid false positive with tme manipulation (strict equality sometimes leads to error of 1 second)
        $this->assertTrue($lastPullItemContent[1] <= $listExpiration && $listExpiration - 1 <= $lastPullItemContent[1]);
        $this->assertTrue($lastPullItemContent['last_pull'] <= $time && $time - 1 <=
                                                                        $lastPullItemContent['last_pull']);

        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_2_CACHE_KEY));
        $cachedValue = $item->get();
        $this->assertEquals(
            2,
            count($cachedValue),
            'Should now have 2 cached remediation'
        );

        $this->assertEquals(
            'ban',
            $cachedValue[0][0]
        );
        $this->assertEquals(
            'captcha',
            $cachedValue[1][0]
        );
        // Test 12 : new + list again
        // We wait to test that expiration and pull date won't change
        sleep(1);
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 0, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_2_CACHE_KEY));
        $cachedValue = $item->get();
        $this->assertEquals(
            2,
            count($cachedValue),
            'Should now have 2 cached remediation'
        );

        $this->assertEquals(
            'ban',
            $cachedValue[0][0]
        );
        $this->assertEquals(
            'captcha',
            $cachedValue[1][0]
        );
        $lastPullItem = $remediation->getCacheStorage()->getItem($lastPullCacheKey);

        $this->assertEquals(
            [AbstractCache::INDEX_EXP => $listExpiration, AbstractCache::LAST_PULL => $time],
            $lastPullItem->get(),
            'Expiration and pull date should not have change'
        );
        // Test 13 : new + list again
        // We wait to test that expiration and pull date won't change
        $result = $remediation->refreshDecisions();
        PHPUnitUtil::assertRegExp(
            $this,
            '/.*200.*"type":"CAPI_REM_HANDLE_LIST_DECISIONS.*UNIT TEST EXCEPTION"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Prod log content should be correct'
        );

        // Test 14 : new + 1 new with 0h duration
        $remediation->clearCache();
        $result = $remediation->refreshDecisions();
        $this->assertEquals(
            ['new' => 1, 'deleted' => 0],
            $result,
            'Refresh count should be correct'
        );
        $adapter = $this->cacheStorage->getAdapter();
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_2_CACHE_KEY));
        $this->assertEquals(
            false,
            $item->isHit(),
            'Remediation should have been cached'
        );
        $item = $adapter->getItem(base64_encode(TestConstants::IP_V4_CACHE_KEY));
        $this->assertEquals(
            true,
            $item->isHit(),
            'Remediation should have been cached'
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
