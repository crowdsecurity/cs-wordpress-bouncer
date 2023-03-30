<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use CrowdSec\Common\Logger\FileLog;
use CrowdSec\LapiClient\Bouncer;
use CrowdSec\RemediationEngine\CacheStorage\Memcached;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CacheStorage\Redis;
use CrowdSec\RemediationEngine\Constants;
use CrowdSec\RemediationEngine\LapiRemediation;

$ip = $argv[1] ?? null;

if (!$ip) {
    exit(
        'Usage: php get-remediation-lapi.php <IP> <BOUNCER_KEY> <LAPI_URL> <STREAM_MODE>' . \PHP_EOL .
        'Example: php get-remediation-lapi.php 172.0.0.24 c580ebdff45da6e01415ed0e9bc9c06b  https://crowdsec:8080 0' .
        \PHP_EOL
    );
}
$bouncerKey = $argv[2] ?? false;
$lapiUrl = $argv[3] ?? false;
$streamMode = isset($argv[4]) ? (bool) $argv[4] : true;
if (!$bouncerKey || !$lapiUrl) {
    exit('Params <BOUNCER_KEY> and <LAPI_URL> are required' . \PHP_EOL .
         'Usage: php get-remediation-lapi.php <IP> <BOUNCER_KEY> <LAPI_URL> <STREAM_MODE>' . \PHP_EOL .
         'Example: php get-remediation-lapi.php 172.0.0.24 c580ebdff45da6e01415ed0e9bc9c06b  https://crowdsec:8080 0' .
         \PHP_EOL);
}

// Init  logger
$logger = new FileLog(['debug_mode' => true], 'remediation-engine-logger');
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
// Init LAPI remediation with geolocation
$remediationConfigs = [
    'stream_mode' => $streamMode,
    'geolocation' => [
        'enabled' => true,
        'cache_duration' => 120,
        'type' => Constants::GEOLOCATION_TYPE_MAXMIND,
        'maxmind' => [
            'database_type' => Constants::MAXMIND_COUNTRY,
            'database_path' => __DIR__ . '/../geolocation/GeoLite2-Country.mmdb',
        ],
    ],
];
$remediationEngine = new LapiRemediation($remediationConfigs, $lapiClient, $phpFileCache, $logger);
// Determine the remediation for the given IP
echo $remediationEngine->getIpRemediation($ip) . \PHP_EOL;
