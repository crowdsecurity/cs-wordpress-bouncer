<?php
/**
 * Plugin Name: CrowdSec
 * Plugin URI: https://github.com/crowdsecurity/cs-wordpress-bouncer
 * Description: Safer Together. Protect your WordPress application with CrowdSec.
 * Tags: crowdsec-bouncer, wordpress, security, firewall, captcha, ip-scanner, ip-blocker, ip-blocking, ip-address, ip-database, ip-range-check, crowdsec, ban-hosts, ban-management, anti-hacking, hacker-protection, captcha-image, captcha-generator, captcha-generation, captcha-service
 * Version: 2.5.0
 * Author: CrowdSec
 * Author URI: https://www.crowdsec.net/
 * Github: https://github.com/crowdsecurity/cs-wordpress-bouncer
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Requires PHP: 7.2
 * Requires at least: 4.9
 * Tested up to: 6.2
 * Stable tag: 2.5.0
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
