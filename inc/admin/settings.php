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
    add_settings_field('crowdsec_api_url', 'LAPI URL', function ($args) {
        $name = $args["label_for"];
        $placeholder = $args["placeholder"];
        $value = esc_attr(get_option("crowdsec_api_url"));

        if (false) { // TODO check if its URL
            echo "Incorrect URL " . $value . ".\n";
        }
        echo '<input type="text" class="regular-text" name="' . $name . '" value="' . $value . '"' .
            ' placeholder="' . $placeholder . '">' .
            '<p>If the CrowdSec Agent is installed on this server, you will set this field to http://localhost:8080.</p>';
    }, 'crowdsec_settings', 'crowdsec_admin_connection', array(
        'label_for' => 'crowdsec_api_url',
        'placeholder' => 'Your LAPI URL',
    ));
    register_setting('crowdsec_plugin_settings', 'crowdsec_api_url', function ($input) {
        $input = esc_attr($input);
        if (false) { // P2 TODO ping API to see if it's available
            $crowdsec_activated = esc_attr(get_option("crowdsec_api_url"));
            if ($crowdsec_activated) {
                add_settings_error("LAPI URL", "crowdsec_error", "LAPI URL " . $input . " is not reachable.");
                return $input;
            }
        }
        return $input;
    });

    // Field "crowdsec_api_key"
    add_settings_field('crowdsec_api_key', 'Bouncer API key', function ($args) {
        $name = $args["label_for"];
        $placeholder = $args["placeholder"];
        $value = esc_attr(get_option("crowdsec_api_key"));

        if (false) { // TODO check api key format / ping api
            echo "Incorrect URL " . $value . ".\n";
        }
        echo '<input style="width: 280px;" type="text" class="regular-text" name="' . $name . '"' .
            ' value="' . $value . '" placeholder="' . $placeholder . '"><p>Generated with the cscli command, ex: <em>cscli bouncers add wordpress-bouncer</em></p>';
    }, 'crowdsec_settings', 'crowdsec_admin_connection', array(
        'label_for' => 'crowdsec_api_key',
        'placeholder' => 'Your bouncer key',
    ));
    register_setting('crowdsec_plugin_settings', 'crowdsec_api_key', function ($input) {
        $input = esc_attr($input);
        if (false) { // P2 TODO check api key length and format (regex)
            $crowdsec_activated = esc_attr(get_option("crowdsec_api_key"));
            if ($crowdsec_activated) {
                add_settings_error("LAPI URL", "crowdsec_error", "LAPI URL " . $input . " is not reachable.");
                return $input;
            }
        }
        return $input;
    });

    /************************************
     ** Section "Bouncing refinements" **
     ***********************************/

    add_settings_section('crowdsec_admin_boucing', 'Bouncing', function () {
        echo "Refine bouncing according to your needs.";
    }, 'crowdsec_settings');

    // Field "crowdsec_bouncing_level"
    add_settings_field('crowdsec_bouncing_level', 'Bouncing level', function ($args) {
?>
        <select name="crowdsec_bouncing_level">
            <option value="<?php echo CROWDSEC_BOUNCING_LEVEL_DISABLED ?>" <?php selected(get_option('crowdsec_bouncing_level'), CROWDSEC_BOUNCING_LEVEL_DISABLED); ?>>🚫 Bouncing disabled</option>
            <option value="<?php echo CROWDSEC_BOUNCING_LEVEL_FLEX ?>" <?php selected(get_option('crowdsec_bouncing_level'), CROWDSEC_BOUNCING_LEVEL_FLEX); ?>>😎 Flex bouncing</option>
            <option value="<?php echo CROWDSEC_BOUNCING_LEVEL_NORMAL ?>" <?php selected(get_option('crowdsec_bouncing_level'), CROWDSEC_BOUNCING_LEVEL_NORMAL); ?>>🛡️ Normal bouncing</option>
            <option value="<?php echo CROWDSEC_BOUNCING_LEVEL_PARANOID ?>" <?php selected(get_option('crowdsec_bouncing_level'), CROWDSEC_BOUNCING_LEVEL_PARANOID); ?>>🕵️ Paranoid mode</option>
        </select>
        <p>
            Select one of the four bouncing modes:<br>
            <ul>
                <li><i>Bouncing disabled</i>: No ban or Captcha display to users. The road is free, even for attackers.</li>
                <li><i>Flex bouncing</i>: Display Captcha only, even if CrowdSec advises to ban the IP.</li>
                <li><i>Normal bouncing</i>: Follow CrowdSec advice (Ban or Captcha).</li>
                <li><i>Paranoid mode</i>: Ban IPs when there are in the CrowdSec database, even if CrowdSec advises to display a Captcha.</li>
            </ul>
        </p>
<?php
    }, 'crowdsec_settings', 'crowdsec_admin_boucing', array(
        'label_for' => 'crowdsec_bouncing_level',
        'class' => 'ui-toggle'
    ));
    register_setting('crowdsec_plugin_settings', 'crowdsec_bouncing_level', function ($input) {
        $input = esc_attr($input);
        if (!in_array($input, [
            CROWDSEC_BOUNCING_LEVEL_DISABLED,
            CROWDSEC_BOUNCING_LEVEL_NORMAL,
            CROWDSEC_BOUNCING_LEVEL_FLEX,
            CROWDSEC_BOUNCING_LEVEL_PARANOID
        ])) {
            $input = CROWDSEC_BOUNCING_LEVEL_DISABLED;
        }
        return $input;
    });

    // Field "crowdsec_public_website_only"
    add_settings_field('crowdsec_public_website_only', 'Public website only', function ($args) {
        $name = $args['label_for'];
        $classes = $args['class'];
        $checkbox = get_option($name);
        $options = esc_attr(get_option('crowdsec_public_website_only'));
        echo '<div class="' . $classes . '"><input type="checkbox" id="' . $name . '" name="' . $name . '"' .
            ' value="' . $options . '" class="" ' . ($checkbox ? 'checked' : '') . '>' .
            '<label for="' . $name . '"><div></div></label></div>' .
            '<p>If enabled, this wp-admin is not bounced, only the public website.</p>';
    }, 'crowdsec_settings', 'crowdsec_admin_boucing', array(
        'label_for' => 'crowdsec_public_website_only',
        'class' => 'ui-toggle'
    ));
    register_setting('crowdsec_plugin_settings', 'crowdsec_public_website_only', 'sanitizeCheckbox');
}
