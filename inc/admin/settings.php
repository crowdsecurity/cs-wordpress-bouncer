<?php
require_once __DIR__ . '/../Constants.php';
use CrowdSecWordPressBouncer\Constants;


function adminSettings()
{
    if(is_multisite()){
        add_action('network_admin_edit_crowdsec_settings', 'crowdsec_multi_save_settings');
    }

    function crowdsec_multi_save_settings()
    {
        if (
            !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crowdsec-settings-update')) {
            wp_nonce_ays('crowdsec_save_settings');
        }

        $options =
            [
                'crowdsec_api_url',
                'crowdsec_auth_type',
                'crowdsec_api_key',
                'crowdsec_tls_cert_path',
                'crowdsec_tls_key_path',
                'crowdsec_tls_ca_cert_path',
                'crowdsec_tls_verify_peer',
                'crowdsec_use_curl',
                'crowdsec_api_timeout',
                'crowdsec_bouncing_level',
                'crowdsec_public_website_only'

            ];

        foreach ( $options as $option ) {
            if ( isset( $_POST[ $option ] ) ) {
                update_site_option( $option, sanitize_text_field($_POST[ $option ]) );
            } else {
                delete_site_option( $option );
            }
        }

        writeStaticConfigFile();

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' => 'crowdsec_plugin',
                    'updated' => true
                ),
                network_admin_url('admin.php')
            )
        );
        exit;
    }

    /**********************************
     ** Section "Connection details" **
     *********************************/

    add_settings_section('crowdsec_admin_connection', 'Connection details', function () {
        echo 'Connect WordPress to your CrowdSec Local API.';
    }, 'crowdsec_settings');

    // Field "crowdsec_api_url"
    addFieldString('crowdsec_api_url', 'Local API URL', 'crowdsec_plugin_settings', 'crowdsec_settings', 'crowdsec_admin_connection', function ($input) {
        return $input;
    }, '',
        'Your Local API URL (e.g. http://localhost:8080)', '');

    // Field "crowdsec_bouncing_level"
    addFieldSelect('crowdsec_auth_type', 'Authentication type', 'crowdsec_plugin_settings', 'crowdsec_settings', 'crowdsec_admin_connection', function ($input) {
        if (!in_array($input, [
            Constants::AUTH_KEY,
            Constants::AUTH_TLS,
        ])) {
            $input = Constants::AUTH_KEY;
            add_settings_error('Bouncing auth type', 'crowdsec_error', 'Auth type: Incorrect authentication type selected.');
        }

        return $input;
    }, '<p class="crowdsec-tls"><b>Important note: </b> If you are using TLS authentication, make sure files are not publicly accessible.<br>
Please refer to <a target="_blank" href="https://github.com/crowdsecurity/cs-wordpress-bouncer/blob/main/docs/USER_GUIDE.md#security">the documentation to deny direct access to this folder.</a></p>', [
        Constants::AUTH_KEY => 'Bouncer API key',
        Constants::AUTH_TLS => 'TLS certificates',
    ]);

    // Field "crowdsec_api_key"
    addFieldString('crowdsec_api_key', 'Bouncer API key', 'crowdsec_plugin_settings', 'crowdsec_settings', 'crowdsec_admin_connection', function ($input) {
        return $input;
    }, '<p>Generated with the cscli command, ex: <em>cscli bouncers add wordpress-bouncer</em></p>', 'Your bouncer key', 'width: 280px;', 'text');

    // Field "crowdsec_tls_cert_path"
    addFieldString('crowdsec_tls_cert_path', 'Path to the bouncer certificate', 'crowdsec_plugin_settings', 'crowdsec_settings', 'crowdsec_admin_connection', function ($input) {
        return $input;
    }, '<p>Absolute path</p>', '/var/crowdsec/tls/bouncer.pem', 'width: 280px;',
        'text');

    // Field "crowdsec_tls_key_path"
    addFieldString('crowdsec_tls_key_path', 'Path to the bouncer key', 'crowdsec_plugin_settings', 'crowdsec_settings',
        'crowdsec_admin_connection', function ($input) {
        return $input;
    }, '<p>Absolute path</p>', '/var/crowdsec/tls/bouncer-key.pem',
        'width: 280px;',
        'text');

    // Field "TLS verify peer"
    addFieldCheckbox('crowdsec_tls_verify_peer', 'Verify peer', 'crowdsec_plugin_settings',
        'crowdsec_settings', 'crowdsec_admin_connection', function () {}, function () {}, '<p>This option determines whether request handler verifies the authenticity of the peer\'s certificate</p>');

    // Field "crowdsec_tls_ca_cert_path"
    addFieldString('crowdsec_tls_ca_cert_path', 'Path to the CA used to process peer verification', 'crowdsec_plugin_settings', 'crowdsec_settings',
        'crowdsec_admin_connection', function ($input) {
            return $input;
        }, '<p>Absolute path</p>', '/var/crowdsec/tls/ca-chain.pem', 'width: 280px;',
        'text');

    // Field "Use cURL"
    addFieldCheckbox('crowdsec_use_curl', 'Use cURL to call Local API', 'crowdsec_plugin_settings',
        'crowdsec_settings', 'crowdsec_admin_connection', function () {}, function () {}, '<p>If checked, calls to Local API will be done with <i>cURL</i> (be sure to have <i>cURL</i> enabled on your system before enabling).
<br>If not checked, calls are done with <i>file_get_contents</i> method (<i>allow_url_fopen</i> is required for this).</p>');

    // Field "timeout"
    addFieldString('crowdsec_api_timeout', 'Local API request timeout', 'crowdsec_plugin_settings', 'crowdsec_settings',
        'crowdsec_admin_connection', function ($input) {
        if ((int) $input === 0) {
            add_settings_error('Local API timeout', 'crowdsec_error', 'Local API timeout: Must be different than 0.');

            return Constants::API_TIMEOUT;
        }

        return (int) $input !== 0 ? (int) $input : Constants::API_TIMEOUT ;
    }, ' seconds. <p>Maximum execution time (in seconds) for a Local API request.<br> Set a negative value (e.g. -1) to allow unlimited request timeout.<br>Default to ' . Constants::API_TIMEOUT .'.',
        Constants::API_TIMEOUT, 'width: 115px;', 'number');

    /************************************
     ** Section "Bouncing refinements" **
     ***********************************/
    add_settings_section('crowdsec_admin_bouncing', 'Bouncing', function () {
        echo 'Refine bouncing according to your needs.';
    }, 'crowdsec_settings');

    // Field "crowdsec_bouncing_level"
    addFieldSelect('crowdsec_bouncing_level', 'Bouncing level', 'crowdsec_plugin_settings', 'crowdsec_settings', 'crowdsec_admin_bouncing', function ($input) {
    	if (!in_array($input, [
            Constants::BOUNCING_LEVEL_DISABLED,
            Constants::BOUNCING_LEVEL_NORMAL,
            Constants::BOUNCING_LEVEL_FLEX,
        ])) {
			$input = Constants::BOUNCING_LEVEL_DISABLED;
			add_settings_error('Bouncing level', 'crowdsec_error', 'Bouncing level: Incorrect bouncing level selected.');
        }

        return $input;
    }, '<p>
    Select one of the three bouncing modes:<br>
    <ul>
        <li><strong>Bouncing disabled</strong>: No ban or Captcha display to users. The road is free, even for attackers.</li>
        <li><strong>Flex bouncing</strong>: Display Captcha only, even if CrowdSec advises to ban the IP.</li>
        <li><strong>Normal bouncing</strong>: Follow CrowdSec advice (Ban or Captcha).</li>
        <!--<li><strong>Paranoid mode</strong>: Ban IPs when there are in the CrowdSec database, even if CrowdSec advises to display a Captcha.</li>-->
    </ul>
</p>', [
        Constants::BOUNCING_LEVEL_DISABLED => '🚫 Bouncing disabled',
        Constants::BOUNCING_LEVEL_FLEX => '😎 Flex bouncing',
        Constants::BOUNCING_LEVEL_NORMAL => '🛡️ Normal bouncing',
    ]);

    addFieldCheckbox('crowdsec_public_website_only', 'Public website only', 'crowdsec_plugin_settings', 'crowdsec_settings', 'crowdsec_admin_bouncing', function () {
        // Stream mode just activated.
        scheduleBlocklistRefresh();
    }, function () {
        // Stream mode just deactivated.
        unscheduleBlocklistRefresh();
    }, '<p>If checked, the wp-admin is not bounced, only the public website</p><p><strong>Important note:</strong> the login page is a common page to both sections. If you want to bounce it, you have to disable "Public website only".</p>');
}
