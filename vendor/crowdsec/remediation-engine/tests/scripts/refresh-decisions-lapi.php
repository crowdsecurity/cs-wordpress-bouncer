<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use CrowdSec\Common\Logger\ConsoleLog;
use CrowdSec\LapiClient\Bouncer;
use CrowdSec\RemediationEngine\CacheStorage\Memcached;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CacheStorage\Redis;
use CrowdSec\RemediationEngine\LapiRemediation;

$bouncerKey = $argv[1] ?? false;
$lapiUrl = $argv[2] ?? false;
if (!$bouncerKey || !$lapiUrl) {
    exit('Params <BOUNCER_KEY> and <LAPI_URL> are required' . \PHP_EOL
         . 'Usage: php refresh-decisions-lapi.php <BOUNCER_KEY> <LAPI_URL>' . \PHP_EOL
         . 'Example: php refresh-decisions-lapi.php 68c2b479830c89bfd48926f9d764da39 https://crowdsec:8080' . \PHP_EOL
    );
}

// Init  logger
$logger = new ConsoleLog();
// Init client
$clientConfigs = [
    'auth_type' => 'api_key',
    'api_url' => $lapiUrl,
    'api_key' => $bouncerKey,
];
$lapiClient = new Bouncer($clientConfigs, null, $logger);
// Init PhpFiles cache storage
$cacheFileConfigs = [
    'fs_cache_path' => __DIR__ . '/.cache/lapi',
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
// Init LAPI remediation
$remediationConfigs = [];
$remediationEngine = new LapiRemediation($remediationConfigs, $lapiClient, $phpFileCache, $logger);
// Retrieve fresh decisions from LAPI and update the cache
echo json_encode($remediationEngine->refreshDecisions()) . \PHP_EOL;
