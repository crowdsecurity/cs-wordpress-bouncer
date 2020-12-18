<?php

use CrowdSecBouncer\Constants;

/**
 * The code that runs during plugin activation
 */
function activate_crowdsec_plugin()
{
    flush_rewrite_rules();

    // Set default options.


    update_option("crowdsec_api_url", '');
    update_option("crowdsec_api_key", '');

    update_option("crowdsec_bouncing_level", CROWDSEC_BOUNCING_LEVEL_NORMAL);
    update_option("crowdsec_public_website_only", true);

    update_option("crowdsec_stream_mode", false);
    update_option("crowdsec_stream_mode_refresh_frequency", 60);

    update_option("crowdsec_cache_system", CROWDSEC_CACHE_SYSTEM_PHPFS);
    update_option("crowdsec_redis_dsn", '');
    update_option("crowdsec_memcached_dsn", '');
    update_option("crowdsec_captcha_technology", CROWDSEC_CAPTCHA_TECHNOLOGY_LOCAL);
    update_option("crowdsec_clean_ip_cache_duration", Constants::CACHE_EXPIRATION_FOR_CLEAN_IP);
    update_option("crowdsec_bad_ip_cache_duration", Constants::CACHE_EXPIRATION_FOR_BAD_IP);
    update_option("crowdsec_fallback_remediation", Constants::REMEDIATION_CAPTCHA);
}



/**
 * The code that runs during plugin deactivation
 */
function deactivate_crowdsec_plugin()
{
    flush_rewrite_rules();

    // Unschedule existing "refresh cache" wp-cron.
    unscheduleBlocklistRefresh();

    $apiUrl = esc_attr(get_option('crowdsec_api_url'));
    $apiKey = esc_attr(get_option('crowdsec_api_key'));
    if (!empty($apiUrl) && !empty($apiKey)) {
        // Clear the bouncer cache.
        clearBouncerCacheInAdminPage();
    }

    // Clean options.

    delete_option("crowdsec_api_url");
    delete_option("crowdsec_api_key");

    delete_option("crowdsec_bouncing_level");
    delete_option("crowdsec_public_website_only");

    delete_option("crowdsec_stream_mode");
    delete_option("crowdsec_stream_mode_refresh_frequency");

    delete_option("crowdsec_cache_system");
    delete_option("crowdsec_redis_dsn");
    delete_option("crowdsec_memcached_dsn");
    delete_option("crowdsec_captcha_technology");
    delete_option("crowdsec_clean_ip_cache_duration");
    delete_option("crowdsec_bad_ip_cache_duration");
    delete_option("crowdsec_fallback_remediation");
}
