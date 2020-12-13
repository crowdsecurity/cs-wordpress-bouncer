<?php

use CrowdSecBouncer\Constants;

/**
 * The code that runs during plugin activation
 */
function activate_crowdsec_plugin()
{
    flush_rewrite_rules();

    // Set default options.
    
    add_option("crowdsec_api_url", '');
    add_option("crowdsec_api_key", '');

    add_option("crowdsec_bouncing_level", "2");
    add_option("crowdsec_public_website_only", true);

    add_option("crowdsec_stream_mode", false);
    add_option("crowdsec_stream_mode_refresh_frequency", "30");
    
    add_option("crowdsec_cache_system", "0");
    add_option("crowdsec_redis_dsn", '');
    add_option("crowdsec_memcached_dsn", '');
    add_option("crowdsec_captcha_technology", "0");
    add_option("crowdsec_clean_ip_cache_duration", Constants::CACHE_EXPIRATION_FOR_CLEAN_IP);
    add_option("crowdsec_fallback_remediation", Constants::REMEDIATION_CAPTCHA);

    // state options
    add_option("crowdsec_stream_mode_warmed_up", false);
}

register_activation_hook(__FILE__, 'activate_crowdsec_plugin');



/**
 * The code that runs during plugin deactivation
 */
function deactivate_crowdsec_plugin()
{
    flush_rewrite_rules();

    // Clear the bouncer cache.

    clearBouncerCache();

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
    delete_option("crowdsec_fallback_remediation");

}
register_deactivation_hook(__FILE__, 'deactivate_crowdsec_plugin');
