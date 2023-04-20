<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\Common\Logger\ConsoleLog;
use CrowdSec\RemediationEngine\CacheStorage\Memcached;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CacheStorage\Redis;
use CrowdSec\RemediationEngine\CapiRemediation;

// Init  logger
$logger = new ConsoleLog();
// Init client
$clientConfigs = [
    'machine_id_prefix' => 'capiclienttest',
    'scenarios' => ['crowdsecurity/http-backdoors-attempts', 'crowdsecurity/http-bad-user-agent'],
];
$capiClient = new Watcher($clientConfigs, new FileStorage(__DIR__), null, $logger);
// Init PhpFiles cache storage
$cacheFileConfigs = [
    'fs_cache_path' => __DIR__ . '/.cache/capi',
    'use_cache_tags' => false,
];
$phpFileCache = new PhpFiles($cacheFileConfigs, $logger);
// Init Memcached cache storage
$cacheMemcachedConfigs = [
    'memcached_dsn' => 'memcached://memcached:11211',
    'use_cache_tags' => false,
];
$memcachedCache = new Memcached($cacheMemcachedConfigs, $logger);
// Init Redis cache storage
$cacheRedisConfigs = [
    'redis_dsn' => 'redis://redis:6379',
    'use_cache_tags' => false,
];
$redisCache = new Redis($cacheRedisConfigs, $logger);
// Init CAPI remediation
$remediationConfigs = [];
$remediationEngine = new CapiRemediation($remediationConfigs, $capiClient, $redisCache, $logger);
// Retrieve fresh decisions from CAPI and update the cache
echo json_encode($remediationEngine->refreshDecisions()) . \PHP_EOL;
