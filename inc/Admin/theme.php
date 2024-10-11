<?php

function themeSettings()
{
    if(is_multisite()){
        add_action('network_admin_edit_crowdsec_theme_settings', 'crowdsec_multi_save_theme_settings');
    }


    function crowdsec_multi_save_theme_settings()
    {
        if (
            !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crowdsec-theme-settings-update')) {
            wp_nonce_ays('crowdsec_save_theme_settings');
        }

        $options =
            [
                'crowdsec_theme_text_captcha_wall_tab_title',
                'crowdsec_theme_text_captcha_wall_title',
                'crowdsec_theme_text_captcha_wall_subtitle',
                'crowdsec_theme_text_captcha_wall_refresh_image_link',
                'crowdsec_theme_text_captcha_wall_captcha_placeholder',
                'crowdsec_theme_text_captcha_wall_send_button',
                'crowdsec_theme_text_captcha_wall_send_button',
                'crowdsec_theme_text_captcha_wall_error_message',
                'crowdsec_theme_text_captcha_wall_footer',
                'crowdsec_theme_text_captcha_wall_footer',
                'crowdsec_theme_text_ban_wall_tab_title',
                'crowdsec_theme_text_ban_wall_title',
                'crowdsec_theme_text_ban_wall_subtitle',
                'crowdsec_theme_text_ban_wall_footer',
                'crowdsec_theme_color_text_primary',
                'crowdsec_theme_color_text_secondary',
                'crowdsec_theme_color_text_button',
                'crowdsec_theme_color_text_error_message',
                'crowdsec_theme_color_background_page',
                'crowdsec_theme_color_background_container',
                'crowdsec_theme_color_background_button',
                'crowdsec_theme_color_background_button_hover',
                'crowdsec_theme_custom_css'

            ];

        foreach ( $options as $option ) {
            if ( isset( $_POST[ $option ] ) ) {
                update_site_option( $option, sanitize_text_field(stripslashes_deep($_POST[ $option ])) );
            } else {
                delete_site_option( $option );
            }
        }

        writeStaticConfigFile();

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' => 'crowdsec_theme_settings',
                    'updated' => true
                ),
                network_admin_url('admin.php')
            )
        );
        exit;
    }
    /******************************************
     ** Section "Captcha wall text contents" **
     *****************************************/

    add_settings_section('crowdsec_theme_captcha_texts', 'Adapt the wording of the Captcha Wall', function () {
        echo 'You can customize the text display on the captcha wall.';
    }, 'crowdsec_theme_settings');

    // Field "crowdsec_theme_text_captcha_wall_tab_title"
    addFieldString('crowdsec_theme_text_captcha_wall_tab_title', 'Browser tab text', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_captcha_texts', function ($input) {
        return $input;
    }, '<p>The text in the browser tab of the captcha wall page.</p>', 'Tab text', 'width: 150px;', 'text');

    // Field "crowdsec_theme_text_captcha_wall_title"
    addFieldString('crowdsec_theme_text_captcha_wall_title', 'Title text', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_captcha_texts', function ($input) {
        return $input;
    }, '<p>The title text of the captcha wall page.</p>', 'Subtitle text', '', 'text');

    // Field "crowdsec_theme_text_captcha_wall_subtitle"
    addFieldString('crowdsec_theme_text_captcha_wall_subtitle', 'Subtitle text', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_captcha_texts', function ($input) {
        return $input;
    }, '<p>The subtitle text of the captcha wall page.</p>', 'Subtitle text', '', 'text');

    // Field "crowdsec_theme_text_captcha_wall_refresh_image_link"
    addFieldString('crowdsec_theme_text_captcha_wall_refresh_image_link', 'Refresh image text', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_captcha_texts', function ($input) {
        return $input;
    }, '<p>The "refresh image" text of the captcha wall page.</p>', 'Refresh image text', '', 'text');

    // Field "crowdsec_theme_text_captcha_wall_captcha_placeholder"
    addFieldString('crowdsec_theme_text_captcha_wall_captcha_placeholder', 'Input placeholder', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_captcha_texts', function ($input) {
        return $input;
    }, '<p>The "refresh image" text of the captcha wall page.</p>', 'Captcha input placeholder', '', 'text');

    // Field "crowdsec_theme_text_captcha_wall_send_button"
    addFieldString('crowdsec_theme_text_captcha_wall_send_button', 'Send button text', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_captcha_texts', function ($input) {
        return $input;
    }, '<p>The "refresh image" text of the captcha wall page.</p>', 'Send button text', '', 'text');

    // Field "crowdsec_theme_text_captcha_wall_error_message"
    addFieldString('crowdsec_theme_text_captcha_wall_error_message', 'Error message', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_captcha_texts', function ($input) {
        return $input;
    }, '<p>The "error message" text of the captcha wall page when a captcha is not successfuly resolved.</p>', 'Error message', '', 'text');

    // Field "crowdsec_theme_text_captcha_wall_footer"
    addFieldString('crowdsec_theme_text_captcha_wall_footer', 'Footer custom message', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_captcha_texts', function ($input) {
        return $input;
    }, '<p>You can add a custom footer text.</p>', 'Custom footer text', '', 'text');

    /**************************************
     ** Section "Ban wall text contents" **
     *************************************/

    add_settings_section('crowdsec_theme_ban_texts', 'Adapt the wording of the Ban Wall', function () {
        echo 'You can customize the text display on the ban wall.';
    }, 'crowdsec_theme_settings');

    // Field "crowdsec_theme_text_ban_wall_tab_title"
    addFieldString('crowdsec_theme_text_ban_wall_tab_title', 'Browser tab text', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_ban_texts', function ($input) {
        return $input;
    }, '<p>The text in the browser tab of the ban wall page.</p>', 'Tab text', 'width: 150px;', 'text');

    // Field "crowdsec_theme_text_ban_wall_title"
    addFieldString('crowdsec_theme_text_ban_wall_title', 'Title text', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_ban_texts', function ($input) {
        return $input;
    }, '<p>The title text of the ban wall page.</p>', 'Subtitle text', '', 'text');

    // Field "crowdsec_theme_text_ban_wall_subtitle"
    addFieldString('crowdsec_theme_text_ban_wall_subtitle', 'Subtitle text', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_ban_texts', function ($input) {
        return $input;
    }, '<p>The subtitle text of the ban wall page.</p>', 'Subtitle text', '', 'text');

    // Field "crowdsec_theme_text_ban_wall_footer"
    addFieldString('crowdsec_theme_text_ban_wall_footer', 'Footer custom message', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_ban_texts', function ($input) {
        return $input;
    }, '<p>You can add a custom footer text.</p>', 'Custom footer text', '', 'text');

    /**********************
     ** Section "Colors" **
     *********************/

    add_settings_section('crowdsec_theme_colors', 'Use your own colors', function () {
        echo 'You can customize remediation wall colors (ban wall and captcha wall).';
    }, 'crowdsec_theme_settings');

    // Field "crowdsec_theme_color_text_primary"
    addFieldString('crowdsec_theme_color_text_primary', 'Primary text color', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_colors', function ($input) {
        return $input;
    }, '<p>The color used for primary text on the two pages.</p>', 'CSS color', 'width: 100px;', 'text');

    // Field "crowdsec_theme_color_text_secondary"
    addFieldString('crowdsec_theme_color_text_secondary', 'Secondary text color', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_colors', function ($input) {
        return $input;
    }, '<p>The color used for secondary text on the two pages.</p>', 'CSS color', 'width: 100px;', 'text');

    // Field "crowdsec_theme_color_text_button"
    addFieldString('crowdsec_theme_color_text_button', 'Button text color', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_colors', function ($input) {
        return $input;
    }, '<p>The color of the text of the button on the captcha wall page.</p>', 'CSS color', 'width: 100px;', 'text');

    // Field "crowdsec_theme_color_text_error_message"
    addFieldString('crowdsec_theme_color_text_error_message', 'Error message text color', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_colors', function ($input) {
        return $input;
    }, '<p>The color used for the error message (when captcha resolution failed).</p>', 'CSS color', 'width: 100px;', 'text');

    // Field "crowdsec_theme_color_background_page"
    addFieldString('crowdsec_theme_color_background_page', 'Page background color', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_colors', function ($input) {
        return $input;
    }, '<p>The background color used of the two pages.</p>', 'CSS color', 'width: 100px;', 'text');

    // Field "crowdsec_theme_color_background_container"
    addFieldString('crowdsec_theme_color_background_container', 'Container background color', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_colors', function ($input) {
        return $input;
    }, '<p>The background color used for the central block on the two pages</p>', 'CSS color', 'width: 100px;', 'text');

    // Field "crowdsec_theme_color_background_button"
    addFieldString('crowdsec_theme_color_background_button', 'Button background color', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_colors', function ($input) {
        return $input;
    }, '<p>The background color used for the captcha validation button.</p>', 'CSS color', 'width: 100px;', 'text');

    // Field "crowdsec_theme_color_background_button_hover"
    addFieldString('crowdsec_theme_color_background_button_hover', 'Button background color (hover)', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_colors', function ($input) {
        return $input;
    }, '<p>The background color used for the captcha validation button when it\'s hover.</p>', 'CSS color', 'width: 100px;', 'text');

    /**************************
     ** Section "Custom CSS" **
     *************************/

    add_settings_section('crowdsec_theme_css', 'Use your own CSS code', function () {
        echo 'You can customize remediation walls with CSS code (ban wall and captcha wall).';
    }, 'crowdsec_theme_settings');

    // Field "crowdsec_theme_custom_css"
    addFieldString('crowdsec_theme_custom_css', 'Custom CSS code', 'crowdsec_plugin_theme_settings', 'crowdsec_theme_settings', 'crowdsec_theme_css', function ($input) {
        return $input;
    }, '<p>The CSS code to use in the remediation wall (ban wall and captcha wall).</p>', 'CSS code...', '', 'text');
}
