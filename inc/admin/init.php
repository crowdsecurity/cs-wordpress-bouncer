<?php

require_once __DIR__ . '/notice.php';

require_once __DIR__ . '/advanced-settings.php';
require_once __DIR__ . '/settings.php';

add_action('admin_notices', [new AdminNotice(), 'displayAdminNotice']);

if (is_admin()) {

    function clear_bouncer_cache()
    {
        try {
            $bouncer = getBouncerInstance();
            $bouncer->clearCache();
            AdminNotice::displaySuccess(__('Cache is now empty!'));
            // TODO P3 i18n the whole lib https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/
        } catch (WordpressCrowdSecBouncerException $e) {
            // TODO log error for debug mode only.
            AdminNotice::displayError($e->getMessage());
        }
        header("Location: {$_SERVER['HTTP_REFERER']}");
        exit(0);
    }

    // ACTIONS
    add_action('admin_post_refresh_cache', 'clear_bouncer_cache');

    // THEME
    add_action('admin_enqueue_scripts', function () {
        // enqueue all our scripts
        wp_enqueue_style('mypluginstyle', CROWDSEC_PLUGIN_URL . 'assets/crowdsec.css');
        wp_enqueue_script('mypluginscript', CROWDSEC_PLUGIN_URL . 'assets/crowdsec.js');
    });

    // PLUGINS LIST
    add_filter("plugin_action_links_" . CROWDSEC_PLUGIN_URL, function ($links) {
        $settings_link = '<a href="admin.php?page=crowdsec_plugin">Settings</a>';
        array_push($links, $settings_link);
        return $links;
    });

    // ADMIN MENU AND PAGES
    add_action('admin_menu', function () {


        function sanitizeCheckbox($input)
        {
            return isset($input);
        }

        /*add_menu_page('CrowdSec Plugin', 'CrowdSec', 'manage_options', 'crowdsec_plugin', function () {
            require_once(CROWDSEC_PLUGIN_PATH . "/templates/dashboard.php");
        }, 'dashicons-shield', 110);
        add_submenu_page('crowdsec_plugin', 'Settings', 'Settings', 'manage_options', 'crowdsec_settings', function () {
            require_once(CROWDSEC_PLUGIN_PATH . "/templates/settings.php");
        });
        */
        add_menu_page('CrowdSec Plugin', 'CrowdSec', 'manage_options', 'crowdsec_plugin', function () {
            require_once(CROWDSEC_PLUGIN_PATH . "/templates/settings.php");
        }, 'dashicons-shield', 110);
        add_submenu_page('crowdsec_plugin', 'Advanced', 'Advanced', 'manage_options', 'crowdsec_advanced_settings', function () {
            require_once(CROWDSEC_PLUGIN_PATH . "/templates/advanced-settings.php");
        });

        add_action('admin_init', function () {
            adminSettings();
            adminAdvancedSettings();
        });
    });
}
