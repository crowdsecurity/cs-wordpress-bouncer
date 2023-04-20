<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Tests\Unit;

/**
 * Test for geolocation.
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
use CrowdSec\RemediationEngine\Geolocation;
use CrowdSec\RemediationEngine\RemediationException;
use CrowdSec\RemediationEngine\Tests\Constants as TestConstants;
use CrowdSec\RemediationEngine\Tests\PHPUnitUtil;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::__construct
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::clear
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getAdapter
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getCacheKey
 * @uses \CrowdSec\RemediationEngine\CacheStorage\PhpFiles::__construct
 * @uses \CrowdSec\RemediationEngine\CacheStorage\PhpFiles::configure
 * @uses \CrowdSec\RemediationEngine\Configuration\Cache\PhpFiles::getConfigTreeBuilder
 * @uses \CrowdSec\RemediationEngine\Configuration\AbstractCache::addCommonNodes
 * @uses \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getItem
 *
 * @covers \CrowdSec\RemediationEngine\Geolocation::__construct()
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::saveItemWithDuration
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::setIpVariables
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getIpCachedVariables
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::getIpVariables
 * @covers \CrowdSec\RemediationEngine\Geolocation::getMaxMindCountryResult
 * @covers \CrowdSec\RemediationEngine\Geolocation::handleCountryResultForIp
 * @covers \CrowdSec\RemediationEngine\CacheStorage\AbstractCache::unsetIpVariables
 * @covers \CrowdSec\RemediationEngine\Geolocation::clearGeolocationCache
 */
final class GeolocationTest extends AbstractRemediation
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
     * @var string
     */
    private $prodFile;
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    public function setUp(): void
    {
        $this->root = vfsStream::setup(TestConstants::TMP_DIR);
        $currentDate = date('Y-m-d');
        $this->debugFile = 'debug-' . $currentDate . '.log';
        $this->prodFile = 'prod-' . $currentDate . '.log';
        $this->logger = new FileLog(['log_directory_path' => $this->root->url(), 'debug_mode' => true]);

        $cachePhpfilesConfigs = ['fs_cache_path' => $this->root->url()];
        $mockedMethods = [];
        $this->cacheStorage = $this->getCacheMock('PhpFilesAdapter', $cachePhpfilesConfigs, $this->logger, $mockedMethods);
    }

    protected function tearDown(): void
    {
        $this->cacheStorage->clear();
    }

    public function maxmindConfigProvider(): array
    {
        return [
            'country database' => [[
                'database_type' => 'country',
                'database_path' => __DIR__ . '/../geolocation/GeoLite2-Country.mmdb',
            ]],
            'city database' => [[
                'database_type' => 'city',
                'database_path' => __DIR__ . '/../geolocation/GeoLite2-City.mmdb',
            ]],
        ];
    }

    private function handleMaxMindConfig(array $maxmindConfig): array
    {
        // Check if MaxMind database exist
        if (!file_exists($maxmindConfig['database_path'])) {
            $this->fail('For this test, there must be a MaxMind Database here: ' . $maxmindConfig['database_path']);
        }

        return [
            'cache_duration' => 0,
            'enabled' => true,
            'type' => 'maxmind',
            'maxmind' => [
                'database_type' => $maxmindConfig['database_type'],
                'database_path' => $maxmindConfig['database_path'],
            ],
        ];
    }

    /**
     * @dataProvider maxmindConfigProvider
     */
    public function testHandleCountryResultForIp(array $maxmindConfig): void
    {
        // Test 1 : do not save result
        $configs = $this->handleMaxMindConfig($maxmindConfig);

        $geolocation = new Geolocation($configs, $this->cacheStorage, $this->logger);

        $result = $geolocation->handleCountryResultForIp(TestConstants::IP_FRANCE, TestConstants::CACHE_DURATION);

        $this->assertEquals('FR', $result['country'], 'Should retrieve correct country');
        $this->assertEquals('', $result['not_found'], 'Special not found case should be empty');
        $this->assertEquals('', $result['error'], 'Error should be empty');

        $adapter = $this->cacheStorage->getAdapter();
        $item = $adapter->getItem(base64_encode(AbstractCache::GEOLOCATION . AbstractCache::SEP . TestConstants::IP_FRANCE));
        $this->assertEquals(
            false,
            $item->isHit(),
            'Country should not have been cached'
        );
        // Test 2 : save result
        $configs['cache_duration'] = 86400;
        $geolocation = new Geolocation($configs, $this->cacheStorage, $this->logger);

        $result = $geolocation->handleCountryResultForIp(TestConstants::IP_FRANCE, TestConstants::CACHE_DURATION);
        $this->assertEquals('FR', $result['country'], 'Should retrieve correct country');
        $adapter = $this->cacheStorage->getAdapter();
        $item = $adapter->getItem(base64_encode(AbstractCache::GEOLOCATION . AbstractCache::SEP . TestConstants::IP_FRANCE));
        $this->assertEquals(
            true,
            $item->isHit(),
            'Country should have been cached'
        );
        $this->assertEquals(
            ['crowdsec_geolocation_country' => 'FR'],
            $item->get(),
            'Country should have been cached with some key'
        );
        $result = $geolocation->handleCountryResultForIp(TestConstants::IP_FRANCE, TestConstants::CACHE_DURATION);
        $this->assertEquals('FR', $result['country'], 'Should retrieve cached country');

        // Clear storage
        $geolocation->clearGeolocationCache(TestConstants::IP_FRANCE);
        $item = $adapter->getItem(base64_encode(AbstractCache::GEOLOCATION . AbstractCache::SEP . TestConstants::IP_FRANCE));
        $this->assertEquals(
            true,
            $item->isHit(),
            'Country should have been cached'
        );
        $this->assertEquals(
            [],
            $item->get(),
            'Item should have been cleaned'
        );

        // Test 3 : no database
        $this->cacheStorage->clear();
        $configs['maxmind']['database_path'] = __DIR__ . '/../geolocation/do-not-exist.mmdb';
        $configs['cache_duration'] = 86400;
        $geolocation = new Geolocation($configs, $this->cacheStorage, $this->logger);

        $result = $geolocation->handleCountryResultForIp(TestConstants::IP_FRANCE, TestConstants::CACHE_DURATION);

        $this->assertEquals('', $result['country'], 'Country should be empty');
        $this->assertEquals('', $result['not_found'], 'Special not found case should be empty');
        $this->assertNotEmpty($result['error'], 'Error should be set');
        $adapter = $this->cacheStorage->getAdapter();
        $item = $adapter->getItem(base64_encode(AbstractCache::GEOLOCATION . AbstractCache::SEP . TestConstants::IP_FRANCE));
        $this->assertEquals(
            false,
            $item->isHit(),
            'Country should not have been cached'
        );

        // Test 4 : not found
        $configs = $this->handleMaxMindConfig($maxmindConfig);
        $configs['cache_duration'] = 86400;
        $geolocation = new Geolocation($configs, $this->cacheStorage, $this->logger);
        $result = $geolocation->handleCountryResultForIp('0.0.0.0', TestConstants::CACHE_DURATION);
        $this->assertEquals('', $result['country'], 'Country should be empty');
        $this->assertEquals('The address 0.0.0.0 is not in the database.', $result['not_found'], 'Special not found case should be set');
        $this->assertEmpty($result['error'], 'Error should be set');
        $adapter = $this->cacheStorage->getAdapter();
        $item = $adapter->getItem(base64_encode(AbstractCache::GEOLOCATION . AbstractCache::SEP . '0.0.0.0'));
        $this->assertEquals(
            true,
            $item->isHit(),
            'Country should have been cached'
        );
        $this->assertEquals(
            ['crowdsec_geolocation_not_found' => 'The address 0.0.0.0 is not in the database.'],
            $item->get(),
            'Special not found case should be cached'
        );
        $result = $geolocation->handleCountryResultForIp('0.0.0.0', TestConstants::CACHE_DURATION);
        $this->assertEquals('The address 0.0.0.0 is not in the database.', $result['not_found'], 'Special not found case should be retrieved from cache');

        // Test 5 : unknown geolocation type
        $configs['type'] = 'unit-test';
        $configs['cache_duration'] = 0;
        $error = '';
        $geolocation = new Geolocation($configs, $this->cacheStorage, $this->logger);

        try {
            $geolocation->handleCountryResultForIp('0.0.0.0', TestConstants::CACHE_DURATION);
        } catch (RemediationException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Unknown Geolocation type:unit-test/',
            $error,
            'Should have throw an error'
        );

        // Test 6: unknown maxmind base type
        $configs['type'] = 'maxmind';
        $configs['cache_duration'] = 0;
        $configs['maxmind']['database_type'] = 'region';
        $geolocation = new Geolocation($configs, $this->cacheStorage, $this->logger);

        $result = $geolocation->handleCountryResultForIp('0.0.0.0', TestConstants::CACHE_DURATION);

        $this->assertEquals(
            'Unknown MaxMind database type:region',
            $result['error'],
            'Should have set error message'
        );
    }
}
