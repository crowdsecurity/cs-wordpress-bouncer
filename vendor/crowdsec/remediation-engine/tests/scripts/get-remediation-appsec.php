<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use CrowdSec\Common\Constants;
use CrowdSec\Common\Logger\FileLog;
use CrowdSec\LapiClient\Bouncer;
use CrowdSec\RemediationEngine\CacheStorage\Memcached;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CacheStorage\Redis;
use CrowdSec\RemediationEngine\LapiRemediation;

$appSecUrl = $argv[1] ?? false;
$ip = $argv[2] ?? null;
$uri = $argv[3] ?? null;
$host = $argv[4] ?? null;
$verb = $argv[5] ?? null;
$bouncerKey = $argv[6] ?? false;
$userAgent = $argv[7] ?? '';

if (!$ip || !$uri || !$host || !$verb || !$bouncerKey || !$appSecUrl || !$userAgent) {
    exit(
        'Usage: php get-remediation-appsec.php <APPSEC_URL> <IP> <URI> <HOST> <VERB> <API_KEY> <USER_AGENT> <HEADERS_JSON> [<RAW_BODY>]' . \PHP_EOL .
        'Example: php get-remediation-appsec.php http://crowdsec:7422  172.0.0.24 /login example.com POST c580ebdff45da6e01415ed0e9bc9c06b ' .
        '\'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:68.0) Gecko/20100101 Firefox/68.0\' ' .
        '\'{"Content-Type":"application/x-www-form-urlencoded","Accept-Language":"en-US,en;q=0.5"}\' ' .
        '\'username=admin\'' . \PHP_EOL
    );
}

$headers = isset($argv[8]) ? json_decode($argv[8], true) : [];
if (is_null($headers)) {
    exit('Param <HEADERS_JSON> is not a valid json' . \PHP_EOL
       . 'Usage: php get-remediation-appsec.php <APPSEC_URL> <IP> <URI> <HOST> <VERB> <API_KEY> <USER_AGENT> <HEADERS_JSON> [<RAW_BODY>]'
       . \PHP_EOL);
}

$rawBody = $argv[9] ?? '';

// Init  logger
$logger = new FileLog(['debug_mode' => true], 'remediation-engine-logger');
// Init client
$clientConfigs = [
    'auth_type' => 'api_key',
    'app_sec_url' => $appSecUrl,
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
$remediationConfigs = [
    'stream_mode' => false,
];
$remediationEngine = new LapiRemediation($remediationConfigs, $lapiClient, $phpFileCache, $logger);

$appSecRequestMethod = $rawBody ? 'POST' : 'GET';
$appSecHeaders = array_merge($headers, [
    Constants::HEADER_APPSEC_API_KEY => $bouncerKey,
    Constants::HEADER_APPSEC_USER_AGENT => $userAgent,
    Constants::HEADER_APPSEC_IP => $ip,
    Constants::HEADER_APPSEC_URI => $uri,
    Constants::HEADER_APPSEC_HOST => $host,
    Constants::HEADER_APPSEC_VERB => $verb,
]);

// Determine the remediation for the given IP
echo 'Calling AppSec: ' . $lapiClient->getConfig('app_sec_url') . ' ...' . \PHP_EOL;
echo 'Headers: ' . \PHP_EOL;
print_r($appSecHeaders);
echo 'Raw body: ' . $rawBody . \PHP_EOL;
echo 'Remediation: ' . $remediationEngine->getAppSecRemediation($appSecHeaders, $rawBody) . \PHP_EOL;
echo 'Origins count: ' . json_encode($remediationEngine->getOriginsCount()) . \PHP_EOL;
