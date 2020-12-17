<?php

require_once __DIR__ . '/notice.php';

require_once __DIR__ . '/advanced-settings.php';
require_once __DIR__ . '/settings.php';

add_action('admin_notices', [new AdminNotice(), 'displayAdminNotice']);

if (is_admin()) {

    function clearBouncerCache($dislaySuccessFlashMessage = true, $noWarmup = false)
    {
        try {
            $bouncer = getBouncerInstance();
            $bouncer->clearCache();
            $message = __('CrowdSec cache has just been cleared.');

            // In stream mode, immediatelly warm the cache up.
            if (!$noWarmup && get_option("crowdsec_stream_mode")) {
                $bouncer->refreshBlocklistCache();
                $message .= __(' As the stream is enabled, the cache has just been warmed up.');
            }

            if ($dislaySuccessFlashMessage) {
                AdminNotice::displaySuccess($message);
            }

            // TODO P3 i18n the whole lib https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/
        } catch (WordpressCrowdSecBouncerException $e) {
            // TODO log error for debug mode only.
            AdminNotice::displayError($e->getMessage());
        }
    }

    // ACTIONS
    add_action('admin_post_refresh_cache', function () {
        clearBouncerCache();
        header("Location: {$_SERVER['HTTP_REFERER']}");
        exit(0);
    });

    // THEME
    add_action('admin_enqueue_scripts', function () {
        // enqueue all our scripts
        wp_enqueue_style('mypluginstyle', CROWDSEC_PLUGIN_URL . 'assets/crowdsec.css');
        wp_enqueue_script('mypluginscript', CROWDSEC_PLUGIN_URL . 'assets/crowdsec.js');
    });

    // PLUGINS LIST
    add_filter("plugin_action_links_" . CROWDSEC_PLUGIN_URL, function ($links) {
        $settings_link = '<a href="admin.php?page=crowdsec_plugin">Settings</a>';
        array_push($links, $settings_link);
        return $links;
    });

    // ADMIN MENU AND PAGES
    add_action('admin_menu', function () {


        function sanitizeCheckbox($input)
        {
            return isset($input);
        }

        function addFieldCheckbox(string $optionName, string $label, string $optionGroup, string $pageName, string $sectionName, callable $onActivation, callable $onDeactivation, $descriptionHtml)
        {
            register_setting($optionGroup, $optionName, function ($input) use ($optionName, $onActivation, $onDeactivation) {
                $input = esc_attr($input);
                $previousState = !empty(get_option($optionName));
                $currentState = !empty($input);

                if ($previousState !== $currentState) {
                    if (!$previousState && $currentState) {
                        $onActivation();
                    }
                    if ($previousState && !$currentState) {
                        $onDeactivation();
                    }
                    getCrowdSecLoggerInstance()->info(null, ['type' => 'WP_SETTING_UPDATE', $optionName => $currentState]);
                }

                return $input;
            });
            add_settings_field($optionName, $label, function ($args) use ($optionName, $descriptionHtml) {
                $name = $args['label_for'];
                $classes = $args['class'];
                $checked = !empty(get_option($optionName));
                echo '<div class="' . $classes . '">' .
                    '<input type="checkbox" id="' . $name . '" name="' . $name . '" ' . ($checked ? 'checked' : '') .
                    ' class=" ' . ($checked ? 'checked' : '') . '">' .
                    '<label for="' . $name . '"><div></div></label></div>' . $descriptionHtml;
            }, $pageName, $sectionName, array(
                'label_for' => $optionName,
                'class' => 'ui-toggle'
            ));
        }

        function addFieldString(string $optionName, string $label, string $optionGroup, string $pageName, string $sectionName, callable $onChange, $descriptionHtml, $placeholder, $inputStyle, $inputType = 'text')
        {
            register_setting($optionGroup, $optionName, function ($input) use ($onChange, $optionName) {
                $currentState = esc_attr($input);
                $previousState = esc_attr(get_option($optionName));

                if ($previousState !== $currentState) {
                    $currentState = $onChange($currentState);
                    getCrowdSecLoggerInstance()->info(null, ['type' => 'WP_SETTING_UPDATE', $optionName => $currentState]);
                }

                return $currentState;
            });
            add_settings_field($optionName, $label, function ($args) use ($descriptionHtml, $optionName, $inputStyle, $inputType) {
                $name = $args["label_for"];
                $placeholder = $args["placeholder"];
                $value = esc_attr(get_option($optionName));
                echo "<input style=\"$inputStyle\" type=\"$inputType\" class=\"regular-text\" name=\"$name\" value=\"$value\" placeholder=\"$placeholder\">$descriptionHtml";
            }, $pageName, $sectionName, array(
                'label_for' => $optionName,
                'placeholder' => $placeholder,
            ));
        }

        function addFieldSelect(string $optionName, string $label, string $optionGroup, string $pageName, string $sectionName, callable $onChange, string $descriptionHtml, array $choices)
        {
            $previousState = esc_attr(get_option($optionName));
            register_setting($optionGroup, $optionName, function ($input) use ($onChange, $optionName, $previousState) {
                $currentState = esc_attr($input);


                if ($previousState !== $currentState) {
                    $currentState = $onChange($currentState);
                    getCrowdSecLoggerInstance()->info(null, ['type' => 'WP_SETTING_UPDATE', $optionName => $currentState]);
                }

                return $currentState;
            });
            add_settings_field($optionName, $label, function () use ($descriptionHtml, $optionName, $previousState, $choices) {
?>
                <select name="<?php echo $optionName ?>">
                    <?php foreach ($choices as $key => $value) : ?>
                        <option value="<?php echo $key ?>" <?php selected($previousState, $key); ?>><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
<?php
                echo $descriptionHtml;
            }, $pageName, $sectionName);
        }

        /*add_menu_page('CrowdSec Plugin', 'CrowdSec', 'manage_options', 'crowdsec_plugin', function () {
            require_once(CROWDSEC_PLUGIN_PATH . "/templates/dashboard.php");
        }, 'dashicons-shield', 110);
        add_submenu_page('crowdsec_plugin', 'Settings', 'Settings', 'manage_options', 'crowdsec_settings', function () {
            require_once(CROWDSEC_PLUGIN_PATH . "/templates/settings.php");
        });
        */
        add_menu_page('CrowdSec Plugin', 'CrowdSec', 'manage_options', 'crowdsec_plugin', function () {
            require_once(CROWDSEC_PLUGIN_PATH . "/templates/settings.php");
        }, 'dashicons-shield', 110);
        add_submenu_page('crowdsec_plugin', 'Advanced', 'Advanced', 'manage_options', 'crowdsec_advanced_settings', function () {
            require_once(CROWDSEC_PLUGIN_PATH . "/templates/advanced-settings.php");
        });

        add_action('admin_init', function () {
            adminSettings();
            adminAdvancedSettings();
        });
    });
}
