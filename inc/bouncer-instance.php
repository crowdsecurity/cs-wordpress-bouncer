<?php

require_once __DIR__.'/constants.php';
require_once __DIR__.'/bouncer-instance-standalone.php';

use CrowdSecBouncer\Bouncer;
use Monolog\Logger;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

$crowdsecRandomLogFolder = get_option('crowdsec_random_log_folder') ?: '';
crowdsecDefineConstants($crowdsecRandomLogFolder);

function getCrowdSecLoggerInstance(): Logger
{
    return getStandAloneCrowdSecLoggerInstance(CROWDSEC_LOG_PATH, (bool) get_option('crowdsec_debug_mode'), CROWDSEC_DEBUG_LOG_PATH);
}

function getCacheAdapterInstance(string $forcedCacheSystem = null): AbstractAdapter
{
    $cacheSystem = esc_attr(get_option('crowdsec_cache_system'));
    $memcachedDsn = esc_attr(get_option('crowdsec_memcached_dsn'));
    $redisDsn = esc_attr(get_option('crowdsec_redis_dsn'));
    $fsCachePath = CROWDSEC_CACHE_PATH;

    return getCacheAdapterInstanceStandAlone($cacheSystem, $memcachedDsn, $redisDsn, $fsCachePath, $forcedCacheSystem);
}

function getBouncerInstance(string $forcedCacheSystem = null): Bouncer
{
    $apiUrl = esc_attr(get_option('crowdsec_api_url'));
    $apiKey = esc_attr(get_option('crowdsec_api_key'));
    $isStreamMode = !empty(get_option('crowdsec_stream_mode'));
    $cleanIpCacheDuration = (int) get_option('crowdsec_clean_ip_cache_duration');
    $badIpCacheDuration = (int) get_option('crowdsec_bad_ip_cache_duration');
    $fallbackRemediation = esc_attr(get_option('crowdsec_fallback_remediation'));
    $bouncingLevel = esc_attr(get_option('crowdsec_bouncing_level'));
    $crowdSecBouncerUserAgent = CROWDSEC_BOUNCER_USER_AGENT;
    $logger = getCrowdSecLoggerInstance();

    $cacheSystem = esc_attr(get_option('crowdsec_cache_system'));
    $memcachedDsn = esc_attr(get_option('crowdsec_memcached_dsn'));
    $redisDsn = esc_attr(get_option('crowdsec_redis_dsn'));
    $fsCachePath = CROWDSEC_CACHE_PATH;

    return getBouncerInstanceStandAlone($apiUrl, $apiKey, $isStreamMode, $cleanIpCacheDuration, $badIpCacheDuration, $fallbackRemediation, $bouncingLevel, $crowdSecBouncerUserAgent, $logger, $cacheSystem, $memcachedDsn, $redisDsn, $fsCachePath, $forcedCacheSystem);
}
