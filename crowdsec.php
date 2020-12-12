<?php
/*
Plugin Name: CrowdSec
Plugin URI: https://www.crowdsec.net/
Description: Wordpressp plugin that doesn't allow IP according to crowdsec
Tags: security, firewall, malware scanner, two factor authentication, captcha, waf, web app firewall, mfa, 2fa
Version 0.0.1
Author: CrowdSec
Author URI: https://www.crowdsec.net/
Github: https://github.com/crowdsecurity/cs-wordpress-blocker
License: MIT
Requires PHP: 7.2
Stable tag: 0.0.1
Text Domain: crowdsec-wp
*/

// TODO P2 check WP minimum compatible version + add a tag: "Requires at least: X.Y"
// TODO P2 check WP maximum compatible version + add a tag: "Tested up to: 4.8"


session_start();
require_once __DIR__ . '/vendor/autoload.php';

class WordpressCrowdsecBouncerException extends \RuntimeException
{
}

require_once __DIR__ . '/inc/constants.php';
require_once __DIR__ . '/inc/plugin-setup.php';
require_once __DIR__ . '/inc/bouncer-instance.php';
require_once __DIR__ . '/inc/admin/init.php';
require_once __DIR__ . '/inc/bounce-current-ip.php';

// Set CRON
//$apiUrl = get_option('crowdsec_stream_mode_refresh_frequency'); TODO P2 set cron task

// Apply bouncing
$blockWhen = (get_option('crowdsec_public_website_only') === "") ? 'init' : 'wp';
add_action($blockWhen, "safelyBounceCurrentIp");
