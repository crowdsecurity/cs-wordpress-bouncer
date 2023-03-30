<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use CrowdSec\Common\Logger\FileLog;
use CrowdSec\LapiClient\Bouncer;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\LapiRemediation;

$bouncerKey = $argv[1] ?? false;
$lapiUrl = $argv[2] ?? false;
if (!$bouncerKey || !$lapiUrl) {
    exit('Params <BOUNCER_KEY> and <LAPI_URL> are required' . \PHP_EOL
         . 'Usage: php prune-cache-lapi.php <BOUNCER_KEY> <LAPI_URL>'
         . \PHP_EOL);
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
// Init LAPI remediation
$remediationConfigs = [];
$remediationEngine = new LapiRemediation($remediationConfigs, $lapiClient, $phpFileCache, $logger);
// Prune the cache (only available for pruneable cache like PhpFiles)
echo $remediationEngine->pruneCache() . \PHP_EOL;
