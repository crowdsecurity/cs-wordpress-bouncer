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

use CrowdSec\Common\Logger\FileLog;
use CrowdSec\LapiClient\Bouncer;
use CrowdSec\LapiClient\TimeoutException;
use CrowdSec\RemediationEngine\CacheStorage\AbstractCache;
use CrowdSec\RemediationEngine\CacheStorage\Memcached;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CacheStorage\Redis;
use CrowdSec\RemediationEngine\Constants;
use CrowdSec\RemediationEngine\LapiRemediation;
use CrowdSec\RemediationEngine\Tests\Constants as TestConstants;
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
 * @uses   \CrowdSec\RemediationEngine\Configuration\Lapi::getConfigTreeBuilder
 * @uses   \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::addGeolocationNodes
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getIpCachedVariables
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getIpVariables
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::saveItemWithDuration()
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::setIpVariables
 * @uses   \CrowdSec\RemediationEngine\Geolocation::__construct
 * @uses   \CrowdSec\RemediationEngine\Geolocation::getMaxMindCountryResult
 * @uses   \CrowdSec\RemediationEngine\Geolocation::handleCountryResultForIp
 * @uses   \CrowdSec\RemediationEngine\CacheStorage\Memcached::getItem
 * @uses   \CrowdSec\RemediationEngine\Configuration\AbstractCache::addCommonNodes
 *
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::handleRemediationFromDecisions
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::getOriginsCount
 *
 * @uses   \CrowdSec\RemediationEngine\AbstractRemediation::sortDecisionsByPriority
 *
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::updateRemediationOriginCount
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::getCacheStorage
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::handleIpV6RangeDecisions
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::getIpType
 * @covers \CrowdSec\RemediationEngine\Decision::setScope
 * @covers \CrowdSec\RemediationEngine\Decision::setValue
 * @covers \CrowdSec\RemediationEngine\Decision::getExpiresAt
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::__construct
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::normalize
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
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::getCountryForIp
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::upsertItem
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::getScopes
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::isWarm
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::warmUp
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::getClient
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::getAppSecRemediation
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::processCachedDecisions
 * @covers \CrowdSec\RemediationEngine\AbstractRemediation::retrieveRemediationFromCachedDecisions
 * @covers \CrowdSec\RemediationEngine\Configuration\Lapi::addAppSecNodes
 * @covers \CrowdSec\RemediationEngine\Configuration\Lapi::validateAppSec
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::parseAppSecDecision
 * @covers \CrowdSec\RemediationEngine\LapiRemediation::validateAppSecHeaders
 */
final class AppSecLapiRemediationTest extends AbstractRemediation
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
     * @var Bouncer
     */
    private $bouncer;

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
        $this->bouncer = $this->getBouncerMock();

        $cachePhpfilesConfigs = ['fs_cache_path' => $this->root->url()];
        $mockedMethods = [];
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
     *
     * @group appsec
     */
    public function testGetAppSecRemediation($cacheType)
    {
        $this->setCache($cacheType);

        $appSecHeaders = [
            Constants::HEADER_APPSEC_USER_AGENT => 'test',
            Constants::HEADER_APPSEC_VERB => 'GET',
            Constants::HEADER_APPSEC_URI => '/test',
            Constants::HEADER_APPSEC_IP => '1.2.3.4',
            Constants::HEADER_APPSEC_HOST => 'test.com',
        ];

        $remediationConfigs = [];

        $this->bouncer->method('getAppSecDecision')->will(
            $this->onConsecutiveCalls(
                ['action' => 'allow', 'http_status' => 202],  // Test 1 : clean request
                ['action' => 'ban', 'http_status' => 403],  // Test 2 : ban request
                ['action' => 'unknown', 'http_status' => 403], // Test 3 : unknown request
                ['action' => 'unknown', 'http_status' => 403], // Test 4 : unknown request with captcha fallback
                $this->throwException(new TimeoutException('Test timeout exception')), // Test 5 : exception
                $this->throwException(new TimeoutException('Test timeout exception')), // Test 6 : exception
                ['key' => 'value'] // Test 7 : response with no action
            )
        );

        // Test with null logger
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, null);
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

        // Test 0: bad header
        unset($appSecHeaders[Constants::HEADER_APPSEC_IP]);
        $result = $remediation->getAppSecRemediation($appSecHeaders, '');
        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $result,
            'Bad header should early return a bypass remediation'
        );
        $appSecHeaders[Constants::HEADER_APPSEC_IP] = '1.2.3.4';

        // Test 1 (AppSec response: clean request)
        $originsCount = $remediation->getOriginsCount();
        $this->assertEquals(
            [],
            $originsCount,
            'Origins count should be empty'
        );
        $result = $remediation->getAppSecRemediation($appSecHeaders, '');

        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $result,
            'Clean request should return a bypass remediation'
        );

        $originsCount = $remediation->getOriginsCount();
        $this->assertEquals(
            ['clean_appsec' => 1],
            $originsCount,
            'Origin count should be cached'
        );
        // Test 2 (AppSec response: bad request)
        $result = $remediation->getAppSecRemediation($appSecHeaders, '');
        $this->assertEquals(
            Constants::REMEDIATION_BAN,
            $result,
            'Bad request should return a ban remediation'
        );
        $originsCount = $remediation->getOriginsCount();
        $this->assertEquals(
            ['clean_appsec' => 1, 'appsec' => 1],
            $originsCount,
            'Origin count should be cached'
        );
        // Test 3 (AppSec response: unknown request)
        $result = $remediation->getAppSecRemediation($appSecHeaders, '');
        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $result,
            'Unknown request should return a bypass (fallback) remediation'
        );
        $originsCount = $remediation->getOriginsCount();
        $this->assertEquals(
            ['clean_appsec' => 1, 'appsec' => 2],
            $originsCount,
            'Origin count should be cached (original appsec response was not a bypass, so it does not increase clean_appsec counter)'
        );
        // Test 4 (AppSec response: unknown request with captcha fallback)
        $remediationConfigs = ['fallback_remediation' => Constants::REMEDIATION_CAPTCHA];
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, $this->logger);
        $result = $remediation->getAppSecRemediation($appSecHeaders, '');
        $this->assertEquals(
            Constants::REMEDIATION_CAPTCHA,
            $result,
            'Unknown request should return a captcha (fallback) remediation'
        );
        $originsCount = $remediation->getOriginsCount();
        $this->assertEquals(
            ['clean_appsec' => 1, 'appsec' => 3],
            $originsCount,
            'Origin count should be cached (original appsec response was not a bypass, so it does not increase clean_appsec counter)'
        );
        // Test 5 (AppSec response: timeout)
        $result = $remediation->getAppSecRemediation($appSecHeaders, '');
        $this->assertEquals(
            Constants::REMEDIATION_CAPTCHA,
            $result,
            'Timeout should return a captcha remediation (default appsec fallback)')
        ;
        // Test 6 (AppSec response: timeout with configured fallback)
        $remediationConfigs = ['appsec_fallback_remediation' => Constants::REMEDIATION_BAN];
        $remediation = new LapiRemediation($remediationConfigs, $this->bouncer, $this->cacheStorage, $this->logger);
        $result = $remediation->getAppSecRemediation($appSecHeaders, '');
        $this->assertEquals(
            Constants::REMEDIATION_BAN,
            $result,
            'Timeout should return a ban remediation (appsec_remediation_fallback setting)')
        ;
        // Test 7 (AppSec response: no action)
        $result = $remediation->getAppSecRemediation($appSecHeaders, '');
        $this->assertEquals(
            Constants::REMEDIATION_BYPASS,
            $result,
            'No action should return a bypass remediation'
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
