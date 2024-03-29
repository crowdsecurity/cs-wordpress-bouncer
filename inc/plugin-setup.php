<?php

use CrowdSecWordPressBouncer\Constants;

require_once __DIR__.'/options-config.php';
require_once __DIR__ . '/Constants.php';


function writeStaticConfigFile($name = null, $newValue = null)
{
    $crowdSecWpPluginOptions = getCrowdSecOptionsConfig();
    $data = [];
    foreach ($crowdSecWpPluginOptions as $option) {
        $data[$option['name']] = is_multisite() ? get_site_option($option['name']) : get_option($option['name']);
    }
    if ($name) {
        $data[$name] = $newValue;
    }
    if (!empty($data['crowdsec_auto_prepend_file_mode'])) {
        $json = json_encode($data);
        file_put_contents(Constants::STANDALONE_CONFIG_PATH, "<?php return '$json';");
    }
}

/**
 * Function that will be run after an update.
 * Beware that this code will be run with the old version of the plugin, and NOT the new one
 *
 * @param $upgrader_object
 * @param $options
 * @return void
 */
function crowdsec_update_completed( $upgrader_object, $options ) {

    // If an update has taken place and the updated type is plugins and the plugins element exists
    if ( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
        foreach( $options['plugins'] as $plugin ) {
            // Check to ensure it is the CrowdSec Plugin
            if( $plugin == plugin_basename(dirname( dirname(__FILE__) ). '/crowdsec.php')) {
                writeStaticConfigFile();
            }
        }
    }
}

/**
 * The code that runs during plugin activation.
 */
function activate_crowdsec_plugin()
{
    flush_rewrite_rules();

    // Set default options.

    $crowdSecWpPluginOptions = getCrowdSecOptionsConfig();
    foreach ($crowdSecWpPluginOptions as $crowdSecWpPluginOption) {
        if ($crowdSecWpPluginOption['autoInit']) {
            if(is_multisite()){
                update_site_option($crowdSecWpPluginOption['name'], $crowdSecWpPluginOption['default']);
            } else {
                update_option($crowdSecWpPluginOption['name'], $crowdSecWpPluginOption['default']);
            }
        }
    }

    writeStaticConfigFile();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_crowdsec_plugin()
{
    flush_rewrite_rules();

    // Unschedule existing "refresh cache" wp-cron.
    unscheduleBlocklistRefresh();

    $apiUrl = is_multisite() ? esc_attr(get_site_option('crowdsec_api_url')) : esc_attr(get_option('crowdsec_api_url'));
    $apiKey = is_multisite() ? esc_attr(get_site_option('crowdsec_api_key')) : esc_attr(get_option('crowdsec_api_key'));
    if (!empty($apiUrl) && !empty($apiKey)) {
        // Clear the bouncer cache.
        clearBouncerCacheInAdminPage();
    }

    // Clean options.
    $crowdSecWpPluginOptions = getCrowdSecOptionsConfig();
    foreach ($crowdSecWpPluginOptions as $crowdSecWpPluginOption) {
        if ($crowdSecWpPluginOption['autoInit']) {
            if(is_multisite()){
                delete_site_option($crowdSecWpPluginOption['name']);
            } else {
                delete_option($crowdSecWpPluginOption['name']);
            }
        }
    }
}
