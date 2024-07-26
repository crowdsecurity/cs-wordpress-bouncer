<?php
/**
 * Plugin Name: CrowdSec
 * Plugin URI: https://github.com/crowdsecurity/cs-wordpress-bouncer
 * Description: Safer Together. Protect your WordPress application with CrowdSec.
 * Tags: security, captcha, ip-blocker, crowdsec, hacker-protection
 * Version: 2.6.7
 * Author: CrowdSec
 * Author URI: https://www.crowdsec.net/
 * Github: https://github.com/crowdsecurity/cs-wordpress-bouncer
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Requires PHP: 7.2
 * Requires at least: 4.9
 * Tested up to: 6.6
 * Stable tag: 2.6.7
 * Text Domain: crowdsec-wp
 * First release: 2021.
 */
require_once __DIR__.'/vendor/autoload.php';

define('CROWDSEC_PLUGIN_PATH', __DIR__);
define('CROWDSEC_PLUGIN_URL', plugin_dir_url(__FILE__));


require_once __DIR__.'/inc/plugin-setup.php';
require_once __DIR__.'/inc/scheduling.php';
register_activation_hook(__FILE__, 'activate_crowdsec_plugin');
register_deactivation_hook(__FILE__, 'deactivate_crowdsec_plugin');
require_once __DIR__.'/inc/admin/init.php';
require_once __DIR__.'/inc/bounce-current-ip.php';

add_action('plugins_loaded', 'safelyBounceCurrentIp');
add_action( 'upgrader_process_complete', 'crowdsec_update_completed', 10, 2 );
