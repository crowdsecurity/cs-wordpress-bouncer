<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\Common\Logger\ConsoleLog;
use CrowdSec\RemediationEngine\CacheStorage\Memcached;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CacheStorage\Redis;
use CrowdSec\RemediationEngine\CapiRemediation;

$ip = $argv[1] ?? null;

if (!$ip) {
    exit(
        'Usage: php get-remediation-capi.php <IP>' . \PHP_EOL .
        'Example: php get-remediation-capi.php 172.0.0.24' .
        \PHP_EOL
    );
}

// Init  logger
$logger = new ConsoleLog([], 'remediation-engine-logger');
// Init client
$clientConfigs = [
    'machine_id_prefix' => 'remediationtest',
    'scenarios' => ['crowdsecurity/http-sensitive-files'],
    'env' => getenv('ENV') ?: 'dev',
];
$capiClient = new Watcher($clientConfigs, new FileStorage(__DIR__, $clientConfigs['env']), null, $logger);

// Init PhpFiles cache storage
$cacheFileConfigs = [
    'fs_cache_path' => __DIR__ . '/.cache/capi',
];
$phpFileCache = new PhpFiles($cacheFileConfigs, $logger);
// Init Memcached cache storage
$cacheMemcachedConfigs = [
    'memcached_dsn' => 'memcached://memcached:11211',
];
$memcachedCache = new Memcached($cacheMemcachedConfigs, $logger);
// Init Redis cache storage
$cacheRedisConfigs = [
    'redis_dsn' => 'redis://redis:6379',
];
$redisCache = new Redis($cacheRedisConfigs, $logger);
// Init CAPI remediation
$remediationConfigs = [];
$remediationEngine = new CapiRemediation($remediationConfigs, $capiClient, $redisCache, $logger);
// Determine the remediation for the given IP
$remediationData = $remediationEngine->getIpRemediation($ip);
echo 'Remediation: ' . $remediationData['remediation'] . \PHP_EOL;
echo 'Remediation origin: ' . $remediationData['origin'] . \PHP_EOL;
