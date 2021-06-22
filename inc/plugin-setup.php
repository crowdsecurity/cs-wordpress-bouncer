<?php

use CrowdSecBouncer\Constants;

/**
 * The code that runs during plugin activation.
 */
function activate_crowdsec_plugin()
{
    flush_rewrite_rules();

    // Set default options.

    update_option('crowdsec_api_url', '');
    update_option('crowdsec_api_key', '');

    update_option('crowdsec_bouncing_level', Constants::BOUNCING_LEVEL_NORMAL);
    update_option('crowdsec_public_website_only', true);

    update_option('crowdsec_stream_mode', false);
    update_option('crowdsec_stream_mode_refresh_frequency', 60);

    update_option('crowdsec_cache_system', Constants::CACHE_SYSTEM_PHPFS);
    update_option('crowdsec_redis_dsn', '');
    update_option('crowdsec_memcached_dsn', '');
    update_option('crowdsec_clean_ip_cache_duration', Constants::CACHE_EXPIRATION_FOR_CLEAN_IP);
    update_option('crowdsec_bad_ip_cache_duration', Constants::CACHE_EXPIRATION_FOR_BAD_IP);
    update_option('crowdsec_fallback_remediation', Constants::REMEDIATION_CAPTCHA);

    update_option('crowdsec_hide_mentions', false);
    update_option('crowdsec_trust_ip_forward', '');
    update_option('crowdsec_trust_ip_forward_array', []);

    update_option('crowdsec_theme_color_text_primary', 'black');
    update_option('crowdsec_theme_color_text_secondary', '#AAA');
    update_option('crowdsec_theme_color_text_button', 'white');
    update_option('crowdsec_theme_color_text_error_message', '#b90000');
    update_option('crowdsec_theme_color_background_page', '#eee');
    update_option('crowdsec_theme_color_background_container', 'white');
    update_option('crowdsec_theme_color_background_button', '#626365');
    update_option('crowdsec_theme_color_background_button_hover', '#333');

    update_option('crowdsec_theme_text_captcha_wall_tab_title', 'Oops..');
    update_option('crowdsec_theme_text_captcha_wall_title', 'Hmm, sorry but...');
    update_option('crowdsec_theme_text_captcha_wall_subtitle', 'Please complete the security check.');
    update_option('crowdsec_theme_text_captcha_wall_refresh_image_link', 'refresh image');
    update_option('crowdsec_theme_text_captcha_wall_captcha_placeholder', 'Type here...');
    update_option('crowdsec_theme_text_captcha_wall_send_button', 'CONTINUE');
    update_option('crowdsec_theme_text_captcha_wall_error_message', 'Please try again.');
    update_option('crowdsec_theme_text_captcha_wall_footer', '');

    update_option('crowdsec_theme_text_ban_wall_tab_title', 'Oops..');
    update_option('crowdsec_theme_text_ban_wall_title', '🤭 Oh!');
    update_option('crowdsec_theme_text_ban_wall_subtitle', 'This page is protected against cyber attacks and your IP has been banned by our system.');
    update_option('crowdsec_theme_text_ban_wall_footer', '');

    update_option('crowdsec_theme_custom_css', '');

    if (!get_option('crowdsec_random_log_folder')) {
        update_option('crowdsec_random_log_folder', bin2hex(random_bytes(64)));
    }

    update_option('crowdsec_standalone_mode', false);
}

/**
 * The code that runs during plugin deactivation.
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

    delete_option('crowdsec_api_url');
    delete_option('crowdsec_api_key');

    delete_option('crowdsec_bouncing_level');
    delete_option('crowdsec_public_website_only');

    delete_option('crowdsec_stream_mode');
    delete_option('crowdsec_stream_mode_refresh_frequency');

    delete_option('crowdsec_cache_system');
    delete_option('crowdsec_redis_dsn');
    delete_option('crowdsec_memcached_dsn');
    delete_option('crowdsec_clean_ip_cache_duration');
    delete_option('crowdsec_bad_ip_cache_duration');
    delete_option('crowdsec_fallback_remediation');

    delete_option('crowdsec_hide_mentions');
    delete_option('crowdsec_trust_ip_forward');
    delete_option('crowdsec_trust_ip_forward_array');

    delete_option('crowdsec_theme_color_text_primary');
    delete_option('crowdsec_theme_color_text_secondary');
    delete_option('crowdsec_theme_color_text_button');
    delete_option('crowdsec_theme_color_text_error_message');
    delete_option('crowdsec_theme_color_background_page');
    delete_option('crowdsec_theme_color_background_container');
    delete_option('crowdsec_theme_color_background_button');
    delete_option('crowdsec_theme_color_background_button_hover');

    delete_option('crowdsec_theme_text_captcha_wall_tab_title');
    delete_option('crowdsec_theme_text_captcha_wall_title');
    delete_option('crowdsec_theme_text_captcha_wall_subtitle');
    delete_option('crowdsec_theme_text_captcha_wall_refresh_image_link');
    delete_option('crowdsec_theme_text_captcha_wall_captcha_placeholder');
    delete_option('crowdsec_theme_text_captcha_wall_send_button');
    delete_option('crowdsec_theme_text_captcha_wall_error_message');
    delete_option('crowdsec_theme_text_captcha_wall_footer');

    delete_option('crowdsec_theme_text_ban_wall_tab_title');
    delete_option('crowdsec_theme_text_ban_wall_title');
    delete_option('crowdsec_theme_text_ban_wall_subtitle');
    delete_option('crowdsec_theme_text_ban_wall_footer');

    delete_option('crowdsec_theme_custom_css');
    delete_option('crowdsec_standalone_mode');
}
