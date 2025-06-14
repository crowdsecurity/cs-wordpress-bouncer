<?php
use CrowdSecWordPressBouncer\Constants;
use CrowdSec\LapiClient\Constants as LapiConstants;
use CrowdSecWordPressBouncer\Admin\AdminNotice;


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

    function getIntro()
    {

        $intro ='<p>The <b>Instant WordPress Blocklist</b> is an exclusive feature available through the CrowdSec plugin. <br>
        <p class="submit blaas-button"><a class="button button-primary crowdsec-button"  href="https://buy.stripe.com/00g3cIcu59JVfewaES"
                             target="_blank">Subscribe now</a></p>for only $5/month, proactively block thousands of attackers\' IPs currently targeting WordPress sites.
        </p>'
        . '<p><i>Instructions are available in the <a target="_blank" href="https://doc.crowdsec.net/u/bouncers/wordpress#instant-wordpress-blocklist">public documentation</a></i></p>'
        ;

        return $intro;
    }

    add_settings_section('crowdsec_admin_connection', 'Connection details', function () {
        echo 'Connect WordPress to your CrowdSec Local API.';
    }, 'crowdsec_settings', ['after_section' => '<hr>', 'before_section' => getIntro()]);

    // Field "crowdsec_api_url"
    addFieldString('crowdsec_api_url', 'Local API URL', 'crowdsec_plugin_settings', 'crowdsec_settings', 'crowdsec_admin_connection', function ($input, $default ='') {

        if(empty($input) && $default){
            add_settings_error('Local API URL', 'crowdsec_error', 'Local API URL: Can not be empty. Default value used: ' . $default);
            $input = $default;
        }
        $baasUrl = \CrowdSecBouncer\Constants::BAAS_URL;
        if( 0 === strpos($input, $baasUrl)) {
            $message = __("You have just defined a \"Block As A Service\" URL (url starting with $baasUrl).");
            $message .= '<br><b>Please note the following: </b><ul>';
            $message .= '<li>- The Authentication type must be "Bouncer API key".</li>';
            $message .= '<li>- Stream mode must be enabled (see Communication mode with the Local API in Advanced Settings).</li>';
            $message .= '<li>- Usage Metrics cannot be sent (see Usage Metrics in Advanced Settings).</li>';
            $message .= '<li>- AppSec component cannot be used (see Appsec Component in Advanced Settings).</li>';
            $message .= '</ul>';
            AdminNotice::displayWarning($message);
        }

        return $input;
    }, '',
        'Your Local API URL (e.g. http://localhost:8080)', '', 'text', false, LapiConstants::DEFAULT_LAPI_URL);

    // Field "crowdsec_auth_type"
    addFieldSelect('crowdsec_auth_type', 'Authentication type', 'crowdsec_plugin_settings', 'crowdsec_settings', 'crowdsec_admin_connection', function ($input) {
        if (!in_array($input, [
            Constants::AUTH_KEY,
            Constants::AUTH_TLS,
        ])) {
            $input = Constants::AUTH_KEY;
            add_settings_error('Bouncing auth type', 'crowdsec_error', 'Auth type: Incorrect authentication type selected.');
        }

        $lapiUrl = is_multisite() ? get_site_option('crowdsec_api_url') : get_option('crowdsec_api_url');
        if ($input === Constants::AUTH_TLS && 0 === strpos($lapiUrl, Constants::BAAS_URL)) {
            AdminNotice::displayError("Using TLS authentication with a Block As A Service LAPI ($lapiUrl) is not supported. Rolling back to Bouncer API key authentication.");
            $input = Constants::AUTH_KEY;
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
        'crowdsec_settings', 'crowdsec_admin_connection', function () {return true;}, function () {return false;}, '<p>This option determines whether request handler verifies the authenticity of the peer\'s certificate</p>');

    // Field "crowdsec_tls_ca_cert_path"
    addFieldString('crowdsec_tls_ca_cert_path', 'Path to the CA used to process peer verification', 'crowdsec_plugin_settings', 'crowdsec_settings',
        'crowdsec_admin_connection', function ($input) {
            return $input;
        }, '<p>Absolute path</p>', '/var/crowdsec/tls/ca-chain.pem', 'width: 280px;',
        'text');

    // Field "Use cURL"
    addFieldCheckbox('crowdsec_use_curl', 'Use cURL to call Local API', 'crowdsec_plugin_settings',
        'crowdsec_settings', 'crowdsec_admin_connection', function () {return true;}, function () {return false;}, '<p>If checked, calls to Local API will be done with <i>cURL</i> (be sure to have <i>cURL</i> enabled on your system before enabling).
<br>If not checked, calls are done with <i>file_get_contents</i> method (<i>allow_url_fopen</i> is required for this).</p>');

    // Field "timeout"
    addFieldString('crowdsec_api_timeout', 'Local API request timeout', 'crowdsec_plugin_settings', 'crowdsec_settings',
        'crowdsec_admin_connection', function ($input) {
        if ((int) $input === 0) {
            add_settings_error('Local API timeout', 'crowdsec_error', 'Local API timeout: Must be different than 0.');

            return Constants::API_TIMEOUT;
        }

        return $input ;
    }, ' seconds. <p>Maximum execution time (in seconds) for a Local API request.<br> Set a negative value (e.g. -1) to allow unlimited request timeout.<br>Default to ' . Constants::API_TIMEOUT .'.',
        Constants::API_TIMEOUT, 'width: 115px;', 'number');

    /************************************
     ** Section "Bouncing refinements" **
     ***********************************/
    add_settings_section('crowdsec_admin_bouncing', 'Bouncing', function () {
        echo 'Refine bouncing according to your needs.';
    }, 'crowdsec_settings', ['after_section' => '<hr>']);

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
        return true;
    }, function () {
        return false;
    }, '<p>If checked, Admin related requests are not protected.</p><p><strong>Important notes:</strong> We recommend to leave this setting to OFF in order to apply protection to your WordPress admin:<ol><li>WordPress admin is a frequent target of cyberattack.</li><li>Also, some critical public endpoints are considered "admin" and would be unprotected If this setting was ON.</li></ol></p>');
}
