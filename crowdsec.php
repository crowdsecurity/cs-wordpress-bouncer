<?php
/*
Plugin Name: CrowdSec
Plugin URI: https://www.crowdsec.net/
Description: Safer Together. Protect your WordPress application with CrowdSec.
Tags: security, firewall, malware scanner, two factor authentication, captcha, waf, web app firewall, mfa, 2fa
Version 0.2.0
Author: CrowdSec
Author URI: https://www.crowdsec.net/
Github: https://github.com/crowdsecurity/cs-wordpress-blocker
License: MIT
Requires PHP: 7.2
Stable tag: 0.2.0
Text Domain: crowdsec-wp
*/

session_start();
require_once __DIR__.'/vendor/autoload.php';

define('CROWDSEC_PLUGIN_PATH', __DIR__);
define('CROWDSEC_PLUGIN_URL', plugin_dir_url(__FILE__));

class WordpressCrowdSecBouncerException extends \RuntimeException
{
}

require_once __DIR__.'/inc/constants.php';
require_once __DIR__.'/inc/scheduling.php';
require_once __DIR__.'/inc/plugin-setup.php';
register_activation_hook(__FILE__, 'activate_crowdsec_plugin');
register_deactivation_hook(__FILE__, 'deactivate_crowdsec_plugin');
require_once __DIR__.'/inc/bouncer-instance.php';
require_once __DIR__.'/inc/admin/init.php';
require_once __DIR__.'/inc/bounce-current-ip.php';

// Apply bouncing
add_action('plugins_loaded', 'safelyBounceCurrentIp');
