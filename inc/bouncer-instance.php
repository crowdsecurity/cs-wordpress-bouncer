<?php

require_once __DIR__.'/constants.php';
require_once __DIR__.'/bouncer-instance-standalone.php';

use CrowdSecBouncer\Bouncer;
use CrowdSecBouncer\Constants;
use Monolog\Logger;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

$crowdsecRandomLogFolder = get_option('crowdsec_random_log_folder') ?: '';
crowdsecDefineConstants($crowdsecRandomLogFolder);

function getCrowdSecLoggerInstance(): Logger
{
    return getStandaloneCrowdSecLoggerInstance(CROWDSEC_LOG_PATH, (bool) get_option('crowdsec_debug_mode'), CROWDSEC_DEBUG_LOG_PATH);
}

function getDatabaseCacheSettings(): array
{
    return [
        // Cache settings
        'cache_system' => esc_attr(get_option('crowdsec_cache_system')),
        'fs_cache_path' => CROWDSEC_CACHE_PATH,
        'redis_dsn' =>  esc_attr(get_option('crowdsec_redis_dsn')),
        'memcached_dsn' => esc_attr(get_option('crowdsec_memcached_dsn')),
    ];
}

function getCacheAdapterInstance(array $settings, bool $forcedReload = false): AbstractAdapter
{
    $cacheSystem = $settings['cache_system'];
    $memcachedDsn = $settings['memcached_dsn'];
    $redisDsn = $settings['redis_dsn'];
    $fsCachePath = $settings['fs_cache_path'];

    return getCacheAdapterInstanceStandalone($cacheSystem, $memcachedDsn, $redisDsn, $fsCachePath, $forcedReload);
}

function getDatabaseSettings(): array
{
    return [
        // LAPI connection
        'api_key' => esc_attr(get_option('crowdsec_api_key')),
        'api_url' => esc_attr(get_option('crowdsec_api_url')),
        'api_user_agent' => CROWDSEC_BOUNCER_USER_AGENT,
        'api_timeout' => CrowdSecBouncer\Constants::API_TIMEOUT,
        // Debug
        'debug_mode' => !empty(get_option('crowdsec_debug_mode')),
        'log_directory_path' => CROWDSEC_LOG_BASE_PATH,
        'forced_test_ip' => esc_attr(get_option('crowdsec_forced_test_ip')),
        'display_errors' => !empty(get_option('crowdsec_display_errors')),
        // Bouncer
        'bouncing_level' => esc_attr(get_option('crowdsec_bouncing_level')),
        'trust_ip_forward_array' => get_option('crowdsec_trust_ip_forward_array'),
        'fallback_remediation' => esc_attr(get_option('crowdsec_fallback_remediation')),
        // Cache settings
        'stream_mode' => !empty(get_option('crowdsec_stream_mode')),
        'cache_system' => esc_attr(get_option('crowdsec_cache_system')),
        'fs_cache_path' => CROWDSEC_CACHE_PATH,
        'redis_dsn' => esc_attr(get_option('crowdsec_redis_dsn')),
        'memcached_dsn' => esc_attr(get_option('crowdsec_memcached_dsn')),
        'clean_ip_cache_duration' => (int)get_option('crowdsec_clean_ip_cache_duration') ?:
            Constants::CACHE_EXPIRATION_FOR_CLEAN_IP,
        'bad_ip_cache_duration' => (int)get_option('crowdsec_bad_ip_cache_duration') ?:
            Constants::CACHE_EXPIRATION_FOR_BAD_IP,
        // Geolocation
        'geolocation' => []
    ];
}

function getBouncerInstance(array $configs, bool $forceReload = false): Bouncer
{
    return getBouncerInstanceStandalone($configs, $forceReload);
}
