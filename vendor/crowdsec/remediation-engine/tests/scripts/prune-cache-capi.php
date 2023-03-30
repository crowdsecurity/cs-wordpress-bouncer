<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\Common\Logger\FileLog;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CapiRemediation;

// Init  logger
$logger = new FileLog(['debug_mode' => true], 'remediation-engine-logger');

// Init client
$clientConfigs = [
    'machine_id_prefix' => 'remediationtest',
    'scenarios' => ['crowdsecurity/http-sensitive-files'],
];
$capiClient = new Watcher($clientConfigs, new FileStorage(__DIR__), null, $logger);

// Init PhpFiles cache storage
$cacheFileConfigs = [
    'fs_cache_path' => __DIR__ . '/.cache/capi',
];
$phpFileCache = new PhpFiles($cacheFileConfigs, $logger);
// Init CAPI remediation
$remediationConfigs = [];
$remediationEngine = new CapiRemediation($remediationConfigs, $capiClient, $phpFileCache, $logger);
// Prune the cache (only available for pruneable cache like PhpFiles)
echo $remediationEngine->pruneCache() . \PHP_EOL;
