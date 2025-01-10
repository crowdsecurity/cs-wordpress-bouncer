<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use CrowdSec\Common\Logger\FileLog;
use CrowdSec\LapiClient\Bouncer;
use CrowdSec\RemediationEngine\CacheStorage\Memcached;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CacheStorage\Redis;
use CrowdSec\RemediationEngine\LapiRemediation;

$ip = $argv[1] ?? null;

if (!$ip) {
    exit(
        'Usage: php push-lapi-usage-metrics.php <BOUNCER_KEY> <LAPI_URL>' . \PHP_EOL .
        'Example: php push-lapi-usage-metrics.php c580ebdff45da6e01415ed0e9bc9c06b  https://crowdsec:8080' .
        \PHP_EOL
    );
}
$bouncerKey = $argv[2] ?? false;
$lapiUrl = $argv[3] ?? false;
if (!$bouncerKey || !$lapiUrl) {
    exit('Params <BOUNCER_KEY> and <LAPI_URL> are required' . \PHP_EOL
         . 'Usage: php push-lapi-usage-metrics.php <BOUNCER_KEY> <LAPI_URL>'
         . \PHP_EOL);
}

// Init  logger
$logger = new FileLog(['debug_mode' => true, 'log_directory_path' => __DIR__ . '/.logs'], 'remediation-engine-logger');
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

// Send usage metrics
$sentMetrics = $remediationEngine->pushUsageMetrics('test-remediation-php', 'v0.0.0', 'crowdsec-php-bouncer-test');
echo 'Sent metrics: ' . json_encode($sentMetrics) . \PHP_EOL;
