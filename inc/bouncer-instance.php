<?php

require_once __DIR__.'/bouncer-instance-standalone.php';
require_once __DIR__ . '/Constants.php';

use CrowdSecBouncer\Bouncer;
use Monolog\Logger;



function getCrowdSecLoggerInstance(): Logger
{
    return getStandaloneCrowdSecLoggerInstance(
        (bool) get_option('crowdsec_debug_mode'),
        (bool) get_option('crowdsec_disable_prod_log')
    );
}


function getDatabaseSettings(): array
{
    return [
        // Local API connection
        'api_key' => esc_attr(get_option('crowdsec_api_key')),
        'auth_type' => esc_attr(get_option('crowdsec_auth_type'))?:Constants::AUTH_KEY,
        'tls_cert_path' => Constants::CROWDSEC_BOUNCER_TLS_DIR. '/'.ltrim(esc_attr(get_option('crowdsec_tls_cert_path')), '/'),
        'tls_key_path' => Constants::CROWDSEC_BOUNCER_TLS_DIR. '/'.ltrim(esc_attr(get_option('crowdsec_tls_key_path')), '/'),
        'tls_verify_peer' => !empty(get_option('crowdsec_tls_verify_peer')),
        'tls_ca_cert_path' => Constants::CROWDSEC_BOUNCER_TLS_DIR. '/'.ltrim(esc_attr(get_option('crowdsec_tls_ca_cert_path')), '/'),
        'api_url' => esc_attr(get_option('crowdsec_api_url')),
        'use_curl' => !empty(get_option('crowdsec_use_curl')),
        'api_user_agent' => Constants::CROWDSEC_BOUNCER_USER_AGENT,
        'api_timeout' => Constants::API_TIMEOUT,
        // Debug
        'debug_mode' => !empty(get_option('crowdsec_debug_mode')),
        'log_directory_path' => Constants::CROWDSEC_LOG_BASE_PATH,
        'forced_test_ip' => esc_attr(get_option('crowdsec_forced_test_ip')),
        'forced_test_forwarded_ip' => esc_attr(get_option('crowdsec_forced_test_forwarded_ip')),
        'display_errors' => !empty(get_option('crowdsec_display_errors')),
        // Bouncer
        'bouncing_level' => esc_attr(get_option('crowdsec_bouncing_level'))?:Constants::BOUNCING_LEVEL_DISABLED,
        'trust_ip_forward_array' => get_option('crowdsec_trust_ip_forward_array')?:[],
        'fallback_remediation' => esc_attr(get_option('crowdsec_fallback_remediation')),
        // Cache settings
        'stream_mode' => !empty(get_option('crowdsec_stream_mode')),
        'cache_system' => esc_attr(get_option('crowdsec_cache_system'))?:Constants::CACHE_SYSTEM_PHPFS,
        'fs_cache_path' => Constants::CROWDSEC_CACHE_PATH,
        'redis_dsn' => esc_attr(get_option('crowdsec_redis_dsn')),
        'memcached_dsn' => esc_attr(get_option('crowdsec_memcached_dsn')),
        'clean_ip_cache_duration' => (int)get_option('crowdsec_clean_ip_cache_duration') ?:
            Constants::CACHE_EXPIRATION_FOR_CLEAN_IP,
        'bad_ip_cache_duration' => (int)get_option('crowdsec_bad_ip_cache_duration') ?:
            Constants::CACHE_EXPIRATION_FOR_BAD_IP,
        'captcha_cache_duration' => (int)get_option('crowdsec_captcha_cache_duration') ?:
            Constants::CACHE_EXPIRATION_FOR_CAPTCHA,
        'geolocation_cache_duration' => (int)get_option('crowdsec_geolocation_cache_duration') ?:
            Constants::CACHE_EXPIRATION_FOR_CAPTCHA,
        // Geolocation
        'geolocation' => [
            'enabled' => !empty(get_option('crowdsec_geolocation_enabled')),
            'type' => esc_attr(get_option('crowdsec_geolocation_type')) ?: Constants::GEOLOCATION_TYPE_MAXMIND,
            'save_result' => !empty(get_option('crowdsec_geolocation_save_result')),
            'maxmind' => [
                'database_type' => esc_attr(get_option('crowdsec_geolocation_maxmind_database_type')) ?: Constants::MAXMIND_COUNTRY,
                'database_path' => Constants::CROWDSEC_BOUNCER_GEOLOCATION_DIR. '/'.ltrim(esc_attr(get_option('crowdsec_geolocation_maxmind_database_path')), '/'),
            ]
        ]
    ];
}

function getBouncerInstance(array $configs): Bouncer
{
    return getBouncerInstanceStandalone($configs);
}
