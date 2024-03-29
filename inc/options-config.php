<?php
require_once __DIR__ . '/Constants.php';
use CrowdSecWordPressBouncer\Constants;


function getCrowdSecOptionsConfig(): array
{
    return [
        ['name' => 'crowdsec_api_url', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_auth_type', 'default' => Constants::AUTH_KEY, 'autoInit' => true],
        ['name' => 'crowdsec_tls_cert_path', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_tls_key_path', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_tls_verify_peer', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_tls_ca_cert_path', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_api_key', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_use_curl', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_api_timeout', 'default' => Constants::API_TIMEOUT, 'autoInit' => true],
        ['name' => 'crowdsec_bouncing_level', 'default' => Constants::BOUNCING_LEVEL_DISABLED, 'autoInit' => true],
        ['name' => 'crowdsec_public_website_only', 'default' => 'on', 'autoInit' => true],
        ['name' => 'crowdsec_stream_mode', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_stream_mode_refresh_frequency', 'default' => 60, 'autoInit' => true],
        ['name' => 'crowdsec_cache_system', 'default' => Constants::CACHE_SYSTEM_PHPFS, 'autoInit' => true],
        ['name' => 'crowdsec_redis_dsn', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_memcached_dsn', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_clean_ip_cache_duration', 'default' => Constants::CACHE_EXPIRATION_FOR_CLEAN_IP, 'autoInit' => true],
        ['name' => 'crowdsec_bad_ip_cache_duration', 'default' => Constants::CACHE_EXPIRATION_FOR_BAD_IP, 'autoInit' => true],
        ['name' => 'crowdsec_captcha_cache_duration', 'default' => Constants::CACHE_EXPIRATION_FOR_CAPTCHA,
            'autoInit' => true],
        ['name' => 'crowdsec_fallback_remediation', 'default' => Constants::REMEDIATION_CAPTCHA, 'autoInit' => true],
        ['name' => 'crowdsec_hide_mentions', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_trust_ip_forward_array', 'default' => [], 'autoInit' => true],
        ['name' => 'crowdsec_theme_color_text_primary', 'default' => 'black', 'autoInit' => true],
        ['name' => 'crowdsec_theme_color_text_secondary', 'default' => '#AAA', 'autoInit' => true],
        ['name' => 'crowdsec_theme_color_text_button', 'default' => 'white', 'autoInit' => true],
        ['name' => 'crowdsec_theme_color_text_error_message', 'default' => '#b90000', 'autoInit' => true],
        ['name' => 'crowdsec_theme_color_background_page', 'default' => '#eee', 'autoInit' => true],
        ['name' => 'crowdsec_theme_color_background_container', 'default' => 'white', 'autoInit' => true],
        ['name' => 'crowdsec_theme_color_background_button', 'default' => '#626365', 'autoInit' => true],
        ['name' => 'crowdsec_theme_color_background_button_hover', 'default' => '#333', 'autoInit' => true],
        ['name' => 'crowdsec_theme_text_captcha_wall_tab_title', 'default' => 'Oops..', 'autoInit' => true],
        ['name' => 'crowdsec_theme_text_captcha_wall_title', 'default' => 'Hmm, sorry but...', 'autoInit' => true],
        ['name' => 'crowdsec_theme_text_captcha_wall_subtitle', 'default' => 'Please complete the security check.', 'autoInit' => true],
        ['name' => 'crowdsec_theme_text_captcha_wall_refresh_image_link', 'default' => 'refresh image', 'autoInit' => true],
        ['name' => 'crowdsec_theme_text_captcha_wall_captcha_placeholder', 'default' => 'Type here...', 'autoInit' => true],
        ['name' => 'crowdsec_theme_text_captcha_wall_send_button', 'default' => 'CONTINUE', 'autoInit' => true],
        ['name' => 'crowdsec_theme_text_captcha_wall_error_message', 'default' => 'Please try again.', 'autoInit' => true],
        ['name' => 'crowdsec_theme_text_captcha_wall_footer', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_theme_text_ban_wall_tab_title', 'default' => 'Oops..', 'autoInit' => true],
        ['name' => 'crowdsec_theme_text_ban_wall_title', 'default' => '🤭 Oh!', 'autoInit' => true],
        ['name' => 'crowdsec_theme_text_ban_wall_subtitle', 'default' => 'This page is protected against cyber attacks and your IP has been banned by our system.', 'autoInit' => true],
        ['name' => 'crowdsec_theme_text_ban_wall_footer', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_theme_custom_css', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_debug_mode', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_disable_prod_log', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_custom_user_agent', 'default' => '', 'autoInit' => true],
		['name' => 'crowdsec_display_errors', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_forced_test_ip', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_forced_test_forwarded_ip', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_geolocation_enabled', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_geolocation_cache_duration', 'default' => Constants::CACHE_EXPIRATION_FOR_GEO,
            'autoInit' => true],
        ['name' => 'crowdsec_geolocation_type', 'default' => Constants::GEOLOCATION_TYPE_MAXMIND, 'autoInit' => true],
        ['name' => 'crowdsec_geolocation_maxmind_database_type', 'default' => Constants::MAXMIND_COUNTRY, 'autoInit' => true],
        ['name' => 'crowdsec_geolocation_maxmind_database_path', 'default' => '', 'autoInit' => true],
        ['name' => 'crowdsec_auto_prepend_file_mode', 'default' => '', 'autoInit' => true],
    ];
}

function getDatabaseConfigs(): array
{
    $crowdSecWpPluginOptions = getCrowdSecOptionsConfig();
    $finalConfigs = [];
    foreach ($crowdSecWpPluginOptions as $option) {
        $finalConfigs[$option['name']] = is_multisite() ? get_site_option($option['name']) : get_option($option['name']);
    }

    return $finalConfigs;
}
