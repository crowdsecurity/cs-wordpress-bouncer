<?php

/** @noinspection PhpRedundantCatchClauseInspection */

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Tests\Unit;

/**
 * Test for configurations.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\RemediationEngine\CapiRemediation;
use CrowdSec\RemediationEngine\Configuration\Cache\Memcached as MemcachedConfig;
use CrowdSec\RemediationEngine\Configuration\Cache\PhpFiles as PhpFilesConfig;
use CrowdSec\RemediationEngine\Configuration\Cache\Redis as RedisConfig;
use CrowdSec\RemediationEngine\Configuration\Capi as CapiRemediationConfig;
use CrowdSec\RemediationEngine\Configuration\Lapi as LapiRemediationConfig;
use CrowdSec\RemediationEngine\Constants;
use CrowdSec\RemediationEngine\LapiRemediation;
use CrowdSec\RemediationEngine\Tests\PHPUnitUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * @covers \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::addGeolocationNodes
 * @covers \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::validateCommon
 * @covers \CrowdSec\RemediationEngine\Configuration\Capi::getConfigTreeBuilder
 * @covers \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::addCommonNodes
 * @covers \CrowdSec\RemediationEngine\Configuration\Cache\Redis::getConfigTreeBuilder
 * @covers \CrowdSec\RemediationEngine\Configuration\Cache\Memcached::getConfigTreeBuilder
 * @covers \CrowdSec\RemediationEngine\Configuration\Cache\PhpFiles::getConfigTreeBuilder
 * @covers \CrowdSec\RemediationEngine\Configuration\AbstractRemediation::getDefaultOrderedRemediations
 * @covers \CrowdSec\RemediationEngine\Configuration\Lapi::getConfigTreeBuilder
 * @covers \CrowdSec\RemediationEngine\Configuration\Capi::addCapiNodes
 * @covers \CrowdSec\RemediationEngine\Configuration\AbstractCache::addCommonNodes
 */
final class ConfigurationTest extends TestCase
{
    public function testCapiConfiguration()
    {
        $configuration = new CapiRemediationConfig();
        $processor = new Processor();

        // Test default config
        $configs = [];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'stream_mode' => true,
                'clean_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_CLEAN_IP,
                'bad_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_BAD_IP,
                'fallback_remediation' => 'bypass',
                'ordered_remediations' => array_merge(
                    CapiRemediation::ORDERED_REMEDIATIONS, [Constants::REMEDIATION_BYPASS]
                ),
                'geolocation' => [
                    'cache_duration' => 86400,
                    'enabled' => false,
                    'type' => Constants::GEOLOCATION_TYPE_MAXMIND,
                    'maxmind' => [
                        'database_type' => Constants::MAXMIND_COUNTRY,
                    ],
                ],
                'refresh_frequency_indicator' => 14400,
            ],
            $result,
            'Should set default config'
        );
        // Test to pass some conf
        $configs = ['refresh_frequency_indicator' => 7200, 'clean_ip_cache_duration' => 86400, 'fallback_remediation' => 'ban'];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'stream_mode' => true,
                'clean_ip_cache_duration' => 86400,
                'bad_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_BAD_IP,
                'fallback_remediation' => 'ban',
                'ordered_remediations' => array_merge(
                    CapiRemediation::ORDERED_REMEDIATIONS, [Constants::REMEDIATION_BYPASS]
                ),
                'geolocation' => [
                    'cache_duration' => 86400,
                    'enabled' => false,
                    'type' => Constants::GEOLOCATION_TYPE_MAXMIND,
                    'maxmind' => [
                        'database_type' => Constants::MAXMIND_COUNTRY,
                    ],
                ],
                'refresh_frequency_indicator' => 7200,
            ],
            $result,
            'Should set passed config'
        );
        // Test bypass is always with the lowest priority (i.e. always last element)
        $configs = ['ordered_remediations' => ['rem1', 'rem2']];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'stream_mode' => true,
                'clean_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_CLEAN_IP,
                'bad_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_BAD_IP,
                'fallback_remediation' => 'bypass',
                'ordered_remediations' => ['rem1', 'rem2', 'bypass'],
                'geolocation' => [
                    'cache_duration' => 86400,
                    'enabled' => false,
                    'type' => Constants::GEOLOCATION_TYPE_MAXMIND,
                    'maxmind' => [
                        'database_type' => Constants::MAXMIND_COUNTRY,
                    ],
                ],
                'refresh_frequency_indicator' => 14400,
            ],
            $result,
            'Should add bypass with the lowest priority'
        );
        $configs = ['ordered_remediations' => ['rem1', 'bypass', 'rem2', 'rem3', 'bypass', 'rem4']];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'stream_mode' => true,
                'clean_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_CLEAN_IP,
                'bad_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_BAD_IP,
                'fallback_remediation' => 'bypass',
                'ordered_remediations' => ['rem1', 'rem2', 'rem3', 'rem4', 'bypass'],
                'geolocation' => [
                    'cache_duration' => 86400,
                    'enabled' => false,
                    'type' => Constants::GEOLOCATION_TYPE_MAXMIND,
                    'maxmind' => [
                        'database_type' => Constants::MAXMIND_COUNTRY,
                    ],
                ],
                'refresh_frequency_indicator' => 14400,
            ],
            $result,
            'Should add bypass with the lowest priority'
        );
        // Test array unique
        $configs = ['ordered_remediations' => ['ban', 'test' => 'ban', 'captcha']];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'stream_mode' => true,
                'clean_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_CLEAN_IP,
                'bad_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_BAD_IP,
                'fallback_remediation' => 'bypass',
                'ordered_remediations' => ['ban', 'captcha', 'bypass'],
                'geolocation' => [
                    'cache_duration' => Constants::CACHE_EXPIRATION_FOR_GEO,
                    'enabled' => false,
                    'type' => Constants::GEOLOCATION_TYPE_MAXMIND,
                    'maxmind' => [
                        'database_type' => Constants::MAXMIND_COUNTRY,
                    ],
                ],
                'refresh_frequency_indicator' => 14400,
            ],
            $result,
            'Should normalize config'
        );
        // Test fallback is not in ordered remediations
        $error = '';
        $configs = ['ordered_remediations' => ['ban', 'captcha'], 'fallback_remediation' => 'm2a'];
        try {
            $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        } catch (InvalidConfigurationException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Fallback remediation must belong to ordered remediations./',
            $error,
            'Should throw error if fallback does not belong to ordered remediations'
        );

        // Test fallback is not in ordered remediations but is bypass
        $error = '';
        $configs = ['ordered_remediations' => ['ban', 'captcha'], 'fallback_remediation' => 'bypass'];
        try {
            $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        } catch (InvalidConfigurationException $e) {
            $error = $e->getMessage();
        }

        $this->assertEquals(
            '',
            $error,
            'Should normalize config'
        );
    }

    public function testLapiConfiguration()
    {
        $configuration = new LapiRemediationConfig();
        $processor = new Processor();

        // Test default config
        $configs = [];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'stream_mode' => true,
                'clean_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_CLEAN_IP,
                'bad_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_BAD_IP,
                'fallback_remediation' => 'bypass',
                'ordered_remediations' => array_merge(
                    LapiRemediation::ORDERED_REMEDIATIONS, [Constants::REMEDIATION_BYPASS]
                ),
                'geolocation' => [
                    'cache_duration' => Constants::CACHE_EXPIRATION_FOR_GEO,
                    'enabled' => false,
                    'type' => Constants::GEOLOCATION_TYPE_MAXMIND,
                    'maxmind' => [
                        'database_type' => Constants::MAXMIND_COUNTRY,
                    ],
                ],
            ],
            $result,
            'Should set default config'
        );
        // Test streammode flase
        $configs = ['stream_mode' => false];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'stream_mode' => false,
                'clean_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_CLEAN_IP,
                'bad_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_BAD_IP,
                'fallback_remediation' => 'bypass',
                'ordered_remediations' => array_merge(
                    LapiRemediation::ORDERED_REMEDIATIONS, [Constants::REMEDIATION_BYPASS]
                ),
                'geolocation' => [
                    'cache_duration' => Constants::CACHE_EXPIRATION_FOR_GEO,
                    'enabled' => false,
                    'type' => Constants::GEOLOCATION_TYPE_MAXMIND,
                    'maxmind' => [
                        'database_type' => Constants::MAXMIND_COUNTRY,
                    ],
                ],
            ],
            $result,
            'Should set stream mode false'
        );

        // Test bypass is always with the lowest priority (i.e. always last element)
        $configs = ['ordered_remediations' => ['rem1', 'rem2']];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'stream_mode' => true,
                'clean_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_CLEAN_IP,
                'bad_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_BAD_IP,
                'fallback_remediation' => 'bypass',
                'ordered_remediations' => ['rem1', 'rem2', 'bypass'],
                'geolocation' => [
                    'cache_duration' => Constants::CACHE_EXPIRATION_FOR_GEO,
                    'enabled' => false,
                    'type' => Constants::GEOLOCATION_TYPE_MAXMIND,
                    'maxmind' => [
                        'database_type' => Constants::MAXMIND_COUNTRY,
                    ],
                ],
            ],
            $result,
            'Should add bypass with the lowest priority'
        );
        $configs = ['ordered_remediations' => ['rem1', 'bypass', 'rem2', 'rem3', 'bypass', 'rem4']];
        $result = $processor->processConfiguration($configuration, [$configs]);
        $this->assertEquals(
            [
                'stream_mode' => true,
                'clean_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_CLEAN_IP,
                'bad_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_BAD_IP,
                'fallback_remediation' => 'bypass',
                'ordered_remediations' => ['rem1', 'rem2', 'rem3', 'rem4', 'bypass'],
                'geolocation' => [
                    'cache_duration' => Constants::CACHE_EXPIRATION_FOR_GEO,
                    'enabled' => false,
                    'type' => Constants::GEOLOCATION_TYPE_MAXMIND,
                    'maxmind' => [
                        'database_type' => Constants::MAXMIND_COUNTRY,
                    ],
                ],
            ],
            $result,
            'Should add bypass with the lowest priority'
        );
        // Test array unique
        $configs = ['ordered_remediations' => ['ban', 'test' => 'ban', 'captcha']];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'stream_mode' => true,
                'clean_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_CLEAN_IP,
                'bad_ip_cache_duration' => Constants::CACHE_EXPIRATION_FOR_BAD_IP,
                'fallback_remediation' => 'bypass',
                'ordered_remediations' => ['ban', 'captcha', 'bypass'],
                'geolocation' => [
                    'cache_duration' => Constants::CACHE_EXPIRATION_FOR_GEO,
                    'enabled' => false,
                    'type' => Constants::GEOLOCATION_TYPE_MAXMIND,
                    'maxmind' => [
                        'database_type' => Constants::MAXMIND_COUNTRY,
                    ],
                ],
            ],
            $result,
            'Should normalize config'
        );
        // Test fallback is not in ordered remediations
        $error = '';
        $configs = ['ordered_remediations' => ['ban', 'captcha'], 'fallback_remediation' => 'm2a'];
        try {
            $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        } catch (InvalidConfigurationException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Fallback remediation must belong to ordered remediations./',
            $error,
            'Should throw error if fallback does not belong to ordered remediations'
        );

        // Test fallback is not in ordered remediations but is bypass
        $error = '';
        $configs = ['ordered_remediations' => ['ban', 'captcha'], 'fallback_remediation' => 'bypass'];
        try {
            $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        } catch (InvalidConfigurationException $e) {
            $error = $e->getMessage();
        }

        $this->assertEquals(
            '',
            $error,
            'Should normalize config'
        );
    }

    public function testMemcachedConfiguration()
    {
        $configuration = new MemcachedConfig();
        $processor = new Processor();
        // Test default config
        $configs = ['memcached_dsn' => 'memcached_dsn_test'];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'memcached_dsn' => 'memcached_dsn_test',
            ],
            $result,
            'Should set default config'
        );
        // Test use tags is not set
        $configs = ['memcached_dsn' => 'memcached_dsn_test', 'use_cache_tags' => true];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'memcached_dsn' => 'memcached_dsn_test',
            ],
            $result,
            'Should set default config'
        );

        // Test missing dsn
        $error = '';
        $configs = [];
        try {
            $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        } catch (InvalidConfigurationException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/memcached_dsn.*must be configured/',
            $error,
            'Should throw error if dsn is missing'
        );
        // Test empty dsn
        $error = '';
        $configs = ['memcached_dsn' => ''];
        try {
            $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        } catch (InvalidConfigurationException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/memcached_dsn.*cannot contain an empty value/',
            $error,
            'Should throw error if dsn is empty'
        );
    }

    public function testPhpFilesConfiguration()
    {
        $configuration = new PhpFilesConfig();
        $processor = new Processor();
        // Test default config
        $configs = ['fs_cache_path' => 'fs_cache_path_test'];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'fs_cache_path' => 'fs_cache_path_test',
                'use_cache_tags' => false,
            ],
            $result,
            'Should set default config'
        );

        // Test use tags config
        $configs = ['fs_cache_path' => 'fs_cache_path_test', 'use_cache_tags' => true];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'fs_cache_path' => 'fs_cache_path_test',
                'use_cache_tags' => true,
            ],
            $result,
            'Should set default config'
        );

        // Test missing path
        $error = '';
        $configs = [];
        try {
            $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        } catch (InvalidConfigurationException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/fs_cache_path.*must be configured/',
            $error,
            'Should throw error if dsn is missing'
        );
        // Test empty path
        $error = '';
        $configs = ['fs_cache_path' => ''];
        try {
            $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        } catch (InvalidConfigurationException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/fs_cache_path.*cannot contain an empty value/',
            $error,
            'Should throw error if dsn is empty'
        );
    }

    public function testRedisConfiguration()
    {
        $configuration = new RedisConfig();
        $processor = new Processor();
        // Test default config
        $configs = ['redis_dsn' => 'redis_dsn_test'];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'redis_dsn' => 'redis_dsn_test',
                'use_cache_tags' => false,
            ],
            $result,
            'Should set default config'
        );
        // Test use tags config
        $configs = ['redis_dsn' => 'redis_dsn_test', 'use_cache_tags' => true];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'redis_dsn' => 'redis_dsn_test',
                'use_cache_tags' => true,
            ],
            $result
        );

        // Test config cleaning
        $configs = [
            'redis_dsn' => 'redis_dsn_test',
            'some_useless_conf' => 'what-ever',
            ];
        $result = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        $this->assertEquals(
            [
                'redis_dsn' => 'redis_dsn_test',
                'use_cache_tags' => false,
            ],
            $result,
            'Should clean unexpected config'
        );
        // Test missing dsn
        $error = '';
        $configs = [];
        try {
            $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        } catch (InvalidConfigurationException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/redis_dsn.*must be configured/',
            $error,
            'Should throw error if dsn is missing'
        );
        // Test empty dsn
        $error = '';
        $configs = ['redis_dsn' => ''];
        try {
            $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
        } catch (InvalidConfigurationException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/redis_dsn.*cannot contain an empty value/',
            $error,
            'Should throw error if dsn is empty'
        );
    }
}
