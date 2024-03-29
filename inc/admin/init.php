<?php

use CrowdSecWordPressBouncer\Constants;
use CrowdSecBouncer\BouncerException;
use CrowdSec\RemediationEngine\Geolocation;
use CrowdSecWordPressBouncer\AdminNotice;
use CrowdSecWordPressBouncer\Bouncer;

require_once __DIR__ . '/notice.php';
require_once __DIR__ . '/../Constants.php';
require_once __DIR__ . '/../Bouncer.php';
require_once __DIR__ . '/../options-config.php';

require_once __DIR__.'/settings.php';
require_once __DIR__.'/theme.php';
require_once __DIR__.'/advanced-settings.php';

if(is_multisite()){
    add_action('network_admin_notices', [new AdminNotice(), 'displayAdminNotice']);
}else{
    add_action('admin_notices', [new AdminNotice(), 'displayAdminNotice']);
}


function crowdsec_option_update_callback($name, $oldValue, $newValue)
{
    if (0 === strpos($name, 'crowdsec_')) {
        writeStaticConfigFile($name, $newValue);
    }
}

if (is_admin()) {
    add_action('updated_option', 'crowdsec_option_update_callback', 10, 3);
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
            $configs = getDatabaseConfigs();
            $bouncer = new Bouncer($configs);
            $bouncer->clearCache();
            $message = __('CrowdSec cache has just been cleared.');

            // In stream mode, immediatelly warm the cache up.
            $streamMode = is_multisite() ? get_site_option('crowdsec_stream_mode') : get_option('crowdsec_stream_mode');
            if ($streamMode) {
                $refresh = $bouncer->refreshBlocklistCache();
                $new = $refresh['new']??0;
                $deleted = $refresh['deleted']??0;
                $message .= __(' As the stream mode is enabled, the cache has just been refreshed. New decision(s): '.$new.'. Deleted decision(s): '. $deleted);
            }

            AdminNotice::displaySuccess($message);
        } catch (Exception $e) {
            if(isset($bouncer) && $bouncer->getLogger()){
                $bouncer->getLogger()->error('Exception during cache clearing', [
                    'type' => 'WP_EXCEPTION_WHILE_CLEARING_CACHE',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }

            AdminNotice::displayError('Technical error while clearing the cache: '.$e->getMessage());
        }
    }

    function refreshBouncerCacheInAdminPage()
    {
        try {
            $streamMode = is_multisite() ? get_site_option('crowdsec_stream_mode') : get_option('crowdsec_stream_mode');
            if (!$streamMode) {
                return false;
            }

            // In stream mode, immediately warm the cache up.
            $configs = getDatabaseConfigs();
            $bouncer = new Bouncer($configs);
            $result = $bouncer->refreshBlocklistCache();
            $new = $result['new']??0;
            $deleted = $result['deleted']??0;
            $message = __('The cache has just been refreshed. New decision(s): '.$new.'. Deleted decision(s): '. $deleted);
            AdminNotice::displaySuccess($message);
        } catch (Exception $e) {
            if(isset($bouncer) && $bouncer->getLogger()) {
                $bouncer->getLogger()->error('', [
                    'type' => 'WP_EXCEPTION_WHILE_REFRESHING_CACHE',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
            AdminNotice::displayError('Technical error while refreshing the cache: '.$e->getMessage());
        }
    }

    function pruneBouncerCacheInAdminPage()
    {
        try {
            $configs = getDatabaseConfigs();
            $bouncer = new Bouncer($configs);
            $bouncer->pruneCache();

            AdminNotice::displaySuccess(__('CrowdSec cache has just been pruned.'));
        } catch (Exception $e) {
            if(isset($bouncer) && $bouncer->getLogger()) {
                $bouncer->getLogger()->error('', [
                    'type' => 'WP_EXCEPTION_WHILE_PRUNING',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
            AdminNotice::displayError('Technical error while pruning the cache: '.$e->getMessage());
        }
    }

    function testBouncerConnexionInAdminPage($ip)
    {
        try {
            $configs = getDatabaseConfigs();
            $bouncer = new Bouncer($configs);
            $remediation = $bouncer->getRemediationForIp($ip);
            $message = __("Bouncing has been successfully tested for IP: $ip. Result is: $remediation.");

            AdminNotice::displaySuccess($message);
        } catch (Exception $e) {
            if(isset($bouncer) && $bouncer->getLogger()) {
                $bouncer->getLogger()->error('', [
                    'type' => 'WP_EXCEPTION_WHILE_TESTING_CONNECTION',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
            AdminNotice::displayError('Technical error while testing bouncer connection: '.$e->getMessage());
        }
    }

    function testGeolocationInAdminPage($ip)
    {
        try {
            $maxmindDatabasePath = is_multisite() ? get_site_option('crowdsec_geolocation_maxmind_database_path') :
                get_option('crowdsec_geolocation_maxmind_database_path');
            if (!$maxmindDatabasePath) {
                throw new BouncerException("Maxmind database path can not be empty");
            }
            $configs = getDatabaseConfigs();

            $bouncer = new Bouncer($configs);
            $remediation = $bouncer->getRemediationEngine();
            $cache = $remediation->getCacheStorage();
            $geolocation = new Geolocation($remediation->getConfig('geolocation')??[], $cache, $bouncer->getLogger());
            $countryResult = $geolocation->handleCountryResultForIp($ip);
            if (!empty($countryResult['country'])) {
                $countryMessage = $countryResult['country'];
            } elseif (!empty($countryResult['not_found'])) {
                $countryMessage = $countryResult['not_found'];
            } elseif (!empty($countryResult['error'])) {
                $countryMessage = $countryResult['error'];
            }
            else{
                $countryMessage = __('Something went wrong.');
            }
            $message = __("Geolocation has been tested for IP: $ip. <br>Result is: $countryMessage");

            AdminNotice::displaySuccess($message);
        } catch (Exception $e) {
            if(isset($bouncer) && $bouncer->getLogger()) {
                $bouncer->getLogger()->error('', [
                    'type' => 'WP_EXCEPTION_WHILE_TESTING_GEOLOCATION',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
            AdminNotice::displayError('Technical error while testing geolocation: '.$e->getMessage());
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
    add_action('admin_post_crowdsec_test_connection', function () {
        if (
            !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crowdsec_test_connection')) {
            die('This link expired.');
        }
        $ip = $_POST['crowdsec_test_connection_ip'] ?? $_SERVER['REMOTE_ADDR'];
        testBouncerConnexionInAdminPage($ip);
        header("Location: {$_SERVER['HTTP_REFERER']}");
        exit(0);
    });

    add_action('admin_post_crowdsec_test_geolocation', function () {
        if (
            !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crowdsec_test_geolocation')) {
            die('This link expired.');
        }
        $ip = $_POST['crowdsec_test_geolocation_ip'] ?? $_SERVER['REMOTE_ADDR'];
        testGeolocationInAdminPage($ip);
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

    $adminMenu = is_multisite() ? 'network_admin_menu' : 'admin_menu';

    // ADMIN MENU AND PAGES
    add_action($adminMenu, function () {
        function sanitizeCheckbox($input)
        {
            return isset($input);
        }

        function addFieldCheckbox(string $optionName, string $label, string $optionGroup, string $pageName, string $sectionName, callable $onActivation, callable $onDeactivation, $descriptionHtml)
        {
            register_setting($optionGroup, $optionName, function ($input) use ($optionName, $onActivation, $onDeactivation) {
                $input = esc_attr($input);
                $previousState = is_multisite() ? !empty(get_site_option($optionName)) : !empty(get_option($optionName));
                $currentState = !empty($input);

                if ($previousState !== $currentState) {
                    if (!$previousState && $currentState) {
                        $onActivation();
                    }
                    if ($previousState && !$currentState) {
                        $onDeactivation();
                    }
                }

                return $input;
            });
            add_settings_field($optionName, $label, function ($args) use ($optionName, $descriptionHtml) {
                $name = $args['label_for'];
                $classes = $args['class'];
                $checked = is_multisite() ? !empty(get_site_option($optionName)) : !empty(get_option($optionName));
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
                $previousState = is_multisite() ? esc_attr(get_site_option($optionName)) : esc_attr(get_option($optionName));

                if ($previousState !== $currentState) {
                    $currentState = $onChange($currentState);
                }

                return $currentState;
            });
            add_settings_field($optionName, $label, function ($args) use ($descriptionHtml, $optionName, $inputStyle, $inputType, $disabled) {
                $name = $args['label_for'];
                $placeholder = $args['placeholder'];
                $value = $previousState = is_multisite() ? esc_attr(get_site_option($optionName)) : esc_attr(get_option($optionName));
                echo '<input '.($disabled ? 'disabled="disabled"' : '')." style=\"$inputStyle\" type=\"$inputType\" class=\"regular-text\" name=\"$name\" value=\"$value\" placeholder=\"$placeholder\">$descriptionHtml";
            }, $pageName, $sectionName, [
                'label_for' => $optionName,
                'placeholder' => $placeholder,
            ]);
        }

        function addFieldSelect(string $optionName, string $label, string $optionGroup, string $pageName, string $sectionName, callable $onChange, string $descriptionHtml, array $choices)
        {
            $previousState = $previousState = is_multisite() ? esc_attr(get_site_option($optionName)) : esc_attr(get_option($optionName));
            // Retro compatibility with crowdsec php lib < 0.14.0
            if($optionName === 'crowdsec_bouncing_level'){
            	if($previousState === 'normal_boucing'){
					$previousState = Constants::BOUNCING_LEVEL_NORMAL;
				}elseif($previousState === 'flex_boucing'){
					$previousState = Constants::BOUNCING_LEVEL_FLEX;
				}
			}
            register_setting($optionGroup, $optionName, function ($input) use ($onChange, $optionName, $previousState) {
                $currentState = esc_attr($input);

                if ($previousState !== $currentState) {
                    $currentState = $onChange($currentState);
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
