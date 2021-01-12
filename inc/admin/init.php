<?php

require_once __DIR__.'/notice.php';

require_once __DIR__.'/settings.php';
require_once __DIR__.'/theme.php';
require_once __DIR__.'/advanced-settings.php';

add_action('admin_notices', [new AdminNotice(), 'displayAdminNotice']);

if (is_admin()) {
    function wrapErrorMessage(string $errorMessage)
    {
        return "CrowdSec: $errorMessage";
    }

    function wrapBlockingErrorMessage(string $errorMessage)
    {
        return wrapErrorMessage($errorMessage).
    '<br>Important: Until you fix this problem, <strong>the website will not be protected against attacks</strong>.';
    }

    function clearBouncerCacheInAdminPage()
    {
        try {
            $bouncer = getBouncerInstance();
            $bouncer->clearCache();
            $message = __('CrowdSec cache has just been cleared.');

            // In stream mode, immediatelly warm the cache up.
            if (get_option('crowdsec_stream_mode')) {
                $result = $bouncer->warmBlocklistCacheUp();
                $message .= __(' As the stream mode is enabled, the cache has just been warmed up, '.($result > 0 ? 'there are now '.$result.' decisions' : 'there is now '.$result.' decision').' in cache.');
            }

            AdminNotice::displaySuccess($message);
        } catch (WordpressCrowdSecBouncerException $e) {
            getCrowdSecLoggerInstance()->error('', [
                'type' => 'WP_EXCEPTION_WHILE_CLEARING_CACHE',
                'messsage' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            AdminNotice::displayError('Technical error while clearing the cache: '.$e->getMessage());
        }
    }

    function refreshBouncerCacheInAdminPage()
    {
        try {
            if (!get_option('crowdsec_stream_mode')) {
                return false;
            }

            // In stream mode, immediatelly warm the cache up.
            if (get_option('crowdsec_stream_mode')) {
                $bouncer = getBouncerInstance();
                $result = $bouncer->refreshBlocklistCache();
                AdminNotice::displaySuccess(__(' The cache has just been refreshed ('.($result['new'] > 0 ? $result['new'].' new decisions' : $result['new'].' new decision').', '.$result['deleted'].' deleted).'));
            }
        } catch (WordpressCrowdSecBouncerException $e) {
            getCrowdSecLoggerInstance()->error('', [
                'type' => 'WP_EXCEPTION_WHILE_REFRESHING_CACHE',
                'messsage' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            AdminNotice::displayError('Technical error while refreshing the cache: '.$e->getMessage());
        }
    }

    function pruneBouncerCacheInAdminPage()
    {
        try {
            $bouncer = getBouncerInstance();
            $bouncer->pruneCache();

            AdminNotice::displaySuccess(__('CrowdSec cache has just been pruned.'));
        } catch (WordpressCrowdSecBouncerException $e) {
            getCrowdSecLoggerInstance()->error('', [
                'type' => 'WP_EXCEPTION_WHILE_PRUNING',
                'messsage' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            AdminNotice::displayError('Technical error while pruning the cache: '.$e->getMessage());
        }
    }

    // ACTIONS
    add_action('admin_post_crowdsec_clear_cache', function () {
        if (
            !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crowdsec_clear_cache')) {
            die('This link expired.');
        }
        clearBouncerCacheInAdminPage();
        header("Location: {$_SERVER['HTTP_REFERER']}");
        exit(0);
    });
    add_action('admin_post_crowdsec_refresh_cache', function () {
        if (
            !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crowdsec_refresh_cache')) {
            die('This link expired.');
        }
        refreshBouncerCacheInAdminPage();
        header("Location: {$_SERVER['HTTP_REFERER']}");
        exit(0);
    });
    add_action('admin_post_crowdsec_prune_cache', function () {
        if (
            !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crowdsec_prune_cache')) {
            die('This link expired.');
        }
        pruneBouncerCacheInAdminPage();
        header("Location: {$_SERVER['HTTP_REFERER']}");
        exit(0);
    });

    // THEME
    add_action('admin_enqueue_scripts', function () {
        // enqueue all our scripts
        wp_enqueue_style('mypluginstyle', CROWDSEC_PLUGIN_URL.'/inc/assets/crowdsec.css');
        wp_enqueue_script('mypluginscript', CROWDSEC_PLUGIN_URL.'inc/assets/crowdsec.js');
    });

    // PLUGINS LIST
    add_filter('plugin_action_links_'.CROWDSEC_PLUGIN_URL, function ($links) {
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
                    getCrowdSecLoggerInstance()->info('', ['type' => 'WP_SETTING_UPDATE', $optionName => $currentState]);
                }

                return $input;
            });
            add_settings_field($optionName, $label, function ($args) use ($optionName, $descriptionHtml) {
                $name = $args['label_for'];
                $classes = $args['class'];
                $checked = !empty(get_option($optionName));
                echo '<div class="'.$classes.'">'.
                    '<input type="checkbox" id="'.$name.'" name="'.$name.'" '.($checked ? 'checked' : '').
                    ' class=" '.($checked ? 'checked' : '').'">'.
                    '<label for="'.$name.'"><div></div></label></div>'.$descriptionHtml;
            }, $pageName, $sectionName, [
                'label_for' => $optionName,
                'class' => 'ui-toggle',
            ]);
        }

        function addFieldString(string $optionName, string $label, string $optionGroup, string $pageName, string $sectionName, callable $onChange, $descriptionHtml, $placeholder, $inputStyle, $inputType = 'text', $disabled = false)
        {
            register_setting($optionGroup, $optionName, function ($input) use ($onChange, $optionName) {
                $currentState = esc_attr($input);
                $previousState = esc_attr(get_option($optionName));

                if ($previousState !== $currentState) {
                    $currentState = $onChange($currentState);
                    getCrowdSecLoggerInstance()->info('', ['type' => 'WP_SETTING_UPDATE', $optionName => $currentState]);
                }

                return $currentState;
            });
            add_settings_field($optionName, $label, function ($args) use ($descriptionHtml, $optionName, $inputStyle, $inputType, $disabled) {
                $name = $args['label_for'];
                $placeholder = $args['placeholder'];
                $value = esc_attr(get_option($optionName));
                echo '<input '.($disabled ? 'disabled="disabled"' : '')." style=\"$inputStyle\" type=\"$inputType\" class=\"regular-text\" name=\"$name\" value=\"$value\" placeholder=\"$placeholder\">$descriptionHtml";
            }, $pageName, $sectionName, [
                'label_for' => $optionName,
                'placeholder' => $placeholder,
            ]);
        }

        function addFieldSelect(string $optionName, string $label, string $optionGroup, string $pageName, string $sectionName, callable $onChange, string $descriptionHtml, array $choices)
        {
            $previousState = esc_attr(get_option($optionName));
            register_setting($optionGroup, $optionName, function ($input) use ($onChange, $optionName, $previousState) {
                $currentState = esc_attr($input);

                if ($previousState !== $currentState) {
                    $currentState = $onChange($currentState);
                    getCrowdSecLoggerInstance()->info('', ['type' => 'WP_SETTING_UPDATE', $optionName => $currentState]);
                }

                return $currentState;
            });
            add_settings_field($optionName, $label, function () use ($descriptionHtml, $optionName, $previousState, $choices) {
                ?>
                <select name="<?php echo $optionName; ?>">
                    <?php foreach ($choices as $key => $value) : ?>
                        <option value="<?php echo $key; ?>" <?php selected($previousState, $key); ?>><?php echo $value; ?></option>
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
            require_once CROWDSEC_PLUGIN_PATH.'/inc/templates/settings.php';
        }, 'dashicons-shield', 110);
        add_submenu_page('crowdsec_plugin', 'Theme customization', 'Theme customization', 'manage_options', 'crowdsec_theme_settings', function () {
            require_once CROWDSEC_PLUGIN_PATH.'/inc/templates/theme.php';
        });
        add_submenu_page('crowdsec_plugin', 'Advanced', 'Advanced', 'manage_options', 'crowdsec_advanced_settings', function () {
            require_once CROWDSEC_PLUGIN_PATH.'/inc/templates/advanced-settings.php';
        });

        add_action('admin_init', function () {
            adminSettings();
            themeSettings();
            adminAdvancedSettings();
        });
    });
}
