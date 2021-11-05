<?php

use CrowdSecBouncer\Constants;

function adminSettings()
{
    /**********************************
     ** Section "Connection details" **
     *********************************/

    add_settings_section('crowdsec_admin_connection', 'Connection details', function () {
        echo 'Connect WordPress to your CrowdSec LAPI.';
    }, 'crowdsec_settings');

    // Field "crowdsec_api_url"
    addFieldString('crowdsec_api_url', 'LAPI URL', 'crowdsec_plugin_settings', 'crowdsec_settings', 'crowdsec_admin_connection', function ($input) {
        return $input;
    }, '<p>If the CrowdSec Agent is installed on this server, you will set this field to http://localhost:8080.</p>', 'Your LAPI URL', '');

    // Field "crowdsec_api_key"
    addFieldString('crowdsec_api_key', 'Bouncer API key', 'crowdsec_plugin_settings', 'crowdsec_settings', 'crowdsec_admin_connection', function ($input) {
        return $input;
    }, '<p>Generated with the cscli command, ex: <em>cscli bouncers add wordpress-bouncer</em></p>', 'Your bouncer key', 'width: 280px;', 'text');

    /************************************
     ** Section "Bouncing refinements" **
     ***********************************/

    add_settings_section('crowdsec_admin_boucing', 'Bouncing', function () {
        echo 'Refine bouncing according to your needs.';
    }, 'crowdsec_settings');

    // Field "crowdsec_bouncing_level"
    addFieldSelect('crowdsec_bouncing_level', 'Bouncing level', 'crowdsec_plugin_settings', 'crowdsec_settings', 'crowdsec_admin_boucing', function ($input) {
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

    addFieldCheckbox('crowdsec_public_website_only', 'Public website only', 'crowdsec_plugin_settings', 'crowdsec_settings', 'crowdsec_admin_boucing', function () {
        // Stream mode just activated.
        scheduleBlocklistRefresh();
    }, function () {
        // Stream mode just deactivated.
        unscheduleBlocklistRefresh();
    }, '<p>If enabled, the wp-admin is not bounced, only the public website</p><p><strong>Important note:</strong> the login page is a common page to both sections. If you want to bounce it, you have to disable "Public website only".</p>');
}
