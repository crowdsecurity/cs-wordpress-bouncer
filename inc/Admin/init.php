<?php

use CrowdSec\RemediationEngine\CacheStorage\AbstractCache;
use CrowdSecWordPressBouncer\Constants;
use CrowdSecBouncer\BouncerException;
use CrowdSec\RemediationEngine\Geolocation;
use CrowdSecWordPressBouncer\Admin\AdminNotice;
use CrowdSecWordPressBouncer\Bouncer;

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

    function clearBouncerCacheInAdminPage()
    {
        try {
            $configs = getDatabaseConfigs();
            // If usage metrics are enabled, we need to push them before clearing the cache.
            $isUsageMetricsEnabled = is_multisite() ? get_site_option('crowdsec_usage_metrics') : get_option('crowdsec_usage_metrics');
            $bouncer = new Bouncer($configs);
            if ($isUsageMetricsEnabled) {
                $bouncer->pushUsageMetrics(Constants::BOUNCER_NAME, Constants::VERSION);
            }

            $bouncer->clearCache();
            $message = __('CrowdSec cache has just been cleared.');
            if ($isUsageMetricsEnabled){
                $message .= __('<br>As usage metrics push is enabled, metrics have been pushed before clearing the cache.');
            }
            // In stream mode, immediately warm the cache up.
            $streamMode = is_multisite() ? get_site_option('crowdsec_stream_mode') : get_option('crowdsec_stream_mode');
            if ($streamMode) {
                $refresh = $bouncer->refreshBlocklistCache();
                $new = $refresh['new']??0;
                $deleted = $refresh['deleted']??0;
                $message .= __('<br>As the stream mode is enabled, the cache has just been refreshed. New decision(s): '
                               .$new.'. Deleted decision(s): '. $deleted);
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

    function pushBouncerMetricsInAdminPage()
    {
        try {
            $configs = getDatabaseConfigs();
            $bouncer = new Bouncer($configs);
            $bouncer->pushUsageMetrics(Constants::BOUNCER_NAME, Constants::VERSION);
            AdminNotice::displaySuccess(__('CrowdSec usage metrics have just been pushed.'));
        } catch (Exception $e) {
            if(isset($bouncer) && $bouncer->getLogger()) {
                $bouncer->getLogger()->error('', [
                    'type' => 'WP_EXCEPTION_WHILE_PUSHING_USAGE_METRICS',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
            AdminNotice::displayError('Technical error while pushing usage metrics: '.$e->getMessage());
        }
    }

    function resetBouncerMetricsInAdminPage()
    {
        try {
            $configs = getDatabaseConfigs();
            $bouncer = new Bouncer($configs);
            $bouncer->resetUsageMetrics();
            AdminNotice::displaySuccess(__('CrowdSec usage metrics have been reset successfully.'));
        } catch (Exception $e) {
            if(isset($bouncer) && $bouncer->getLogger()) {
                $bouncer->getLogger()->error('', [
                    'type' => 'WP_EXCEPTION_WHILE_RESETTING_USAGE_METRICS',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
            AdminNotice::displayError('Technical error while resetting usage metrics: '.$e->getMessage());
        }
    }

    function displayBouncerMetricsInAdminPage()
    {
        try {
            $configs = getDatabaseConfigs();
            $bouncer = new Bouncer($configs);
            $metrics = $bouncer->getRemediationEngine()->getOriginsCount();
            $html = '<h3 style="margin-bottom: 5px;">Current metrics</h3>';
            $html .= '<p style="margin-top: 0px; margin-bottom: 10px;">Only metrics collected since last push or cache reset are displayed here.</p>';

            $cacheItem = $bouncer->getRemediationEngine()->getCacheStorage()->getItem(AbstractCache::CONFIG);
            $cacheConfig = $cacheItem->isHit() ? $cacheItem->get() : [];
            $lastSent = $cacheConfig[AbstractCache::LAST_METRICS_SENT] ?? null;

            $lastSentDate = $lastSent
                ? (new DateTime("@$lastSent"))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s') . ' UTC'
                : 'Not available';

            $totalCounts = [];
            $totalCountsByOrigin = [];
            $totalRemediations = 0;

            // Sort origins alphabetically by key
            ksort($metrics);

            $html .= '<table class="widefat striped" style="max-width:700px; margin-top:20px; margin-bottom:50px;">
<thead>
    <tr>
        <th style="text-align:left; padding-left:10px;">Origin</th>
        <th style="text-align:left; padding-left:10px;">Remediation</th>
    </tr>
</thead>
<tbody>';

            foreach ($metrics as $origin => $remediations) {
                // Always add to totalCounts
                foreach ($remediations as $type => $count) {
                    $totalCounts[$type] = ($totalCounts[$type] ?? 0) + $count;
                    $totalCountsByOrigin[$origin] = ($totalCountsByOrigin[$origin] ?? 0) + $count;
                    $totalRemediations += $count;
                }

                if ($origin === AbstractCache::CLEAN || $origin === AbstractCache::CLEAN_APPSEC  ||
                $totalCountsByOrigin[$origin] <= 0) {
                    continue; // Don't display "clean" origin or origin with 0 remediations
                }

                // Sort remediations by remediation type
                ksort($remediations);

                $html .= '<tr>
                <td style="padding-left:10px;">' . esc_html($origin) . '</td>
                <td style="padding-left:10px;">
                    <ul style="margin:0; padding-left:0px;">';

                foreach ($remediations as $type => $count) {
                    $html .= '<li id="metrics-'.$origin.'-'.$type.'">' . esc_html($type) . ': ' . intval($count) . '</li>';
                }

                $html .= '</ul>
                </td>
            </tr>';
            }

            if ($totalRemediations === 0) {
                $html .= '<tr>
                    <td id="metrics-no-new" colspan=2 style="padding-left:10px;text-align:left;">No new metrics</td>
                </tr>';

            }

            // Sort total counts
            ksort($totalCounts);

            // Total row
            $html .= '<tr style="font-weight:bold;">
            <td style="padding-left:10px;">Total</td>
            <td style="padding-left:10px;">
                <ul style="margin:0; padding-left:0px;">';


            foreach ($totalCounts as $type => $count) {
                if ($type === Constants::REMEDIATION_BYPASS || $count <= 0) {
                    continue; // Display "bypass" type after other types
                }
                $html .= '<li id="metrics-total-'.$type.'">' . esc_html($type) . ': ' . intval($count) . '</li>';
            }
            if (!empty($totalCounts[Constants::REMEDIATION_BYPASS])) {
                $html .= '<li id="metrics-total-bypass">' . esc_html(Constants::REMEDIATION_BYPASS) . ': ' . intval
                    ($totalCounts[Constants::REMEDIATION_BYPASS]) . '</li>';
            }

            $html .= '</ul>
            </td>
        </tr>';

            // Last Push row
            $html .= '<tr>
            <td colspan="2" style="padding-left:10px;">
                <strong>Last Push:</strong> ' . esc_html($lastSentDate) . '
            </td>
        </tr>';

            $html .= '</tbody></table>';

            return $html;
        } catch (Exception $e) {
            if (isset($bouncer) && $bouncer->getLogger()) {
                $bouncer->getLogger()->error('', [
                    'type'    => 'WP_EXCEPTION_WHILE_DISPLAYING_USAGE_METRICS',
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ]);
            }

            AdminNotice::displayError('Technical error while displaying usage metrics: ' . esc_html($e->getMessage()));
            return '';
        }
    }


    function displayResetMetricsInAdminPage()
    {
        try {
            $configs = getDatabaseConfigs();
            $bouncer = new Bouncer($configs);
            if ($bouncer->hasBaasUri()) {
                return '<p><input id="crowdsec_reset_usage_metrics" style="margin-right:10px" type="button" value="Reset usage metrics now" class="button button-secondary button-small" onclick="document.getElementById(\'crowdsec_action_reset_usage_metrics\').submit();"></p>';
            }

            return '';
        }
        catch (Exception $e) {
            if (isset($bouncer) && $bouncer->getLogger()) {
                $bouncer->getLogger()->error('', [
                    'type'    => 'WP_EXCEPTION_WHILE_DISPLAYING_RESET_METRICS',
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ]);
            }

            AdminNotice::displayError('Technical error while displaying reset metrics button: ' . esc_html($e->getMessage()));
            return '';
        }

    }

    function displayPushMetricsInAdminPage($isPushEnabled = false)
    {
        try {
            $configs = getDatabaseConfigs();
            $bouncer = new Bouncer($configs);
            if($bouncer->hasBaasUri()) {
                return '';
            }
            if( $isPushEnabled) {
                return '<p><input id="crowdsec_push_usage_metrics" style="margin-right:10px" type="button" value="Push usage metrics now" class="button button-secondary button-small" onclick="document.getElementById(\'crowdsec_action_push_usage_metrics\').submit();"></p>';
            }
            return '<p><input id="crowdsec_push_usage_metrics" style="margin-right:10px" type="button" disabled="disabled" value="Push usage metrics now" class="button button-secondary button-small"></p>';

        }
        catch (Exception $e) {
            if (isset($bouncer) && $bouncer->getLogger()) {
                $bouncer->getLogger()->error('', [
                    'type'    => 'WP_EXCEPTION_WHILE_DISPLAYING_RESET_METRICS',
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ]);
            }

            AdminNotice::displayError('Technical error while displaying reset metrics button: ' . esc_html($e->getMessage()));
            return '';
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
            $remediation = $bouncer->getRemediationForIp($ip)[Constants::REMEDIATION_KEY];
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
    add_action('admin_post_crowdsec_push_usage_metrics', function () {
        if (
            !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crowdsec_push_usage_metrics')) {
            die('This link expired.');
        }
        pushBouncerMetricsInAdminPage();
        header("Location: {$_SERVER['HTTP_REFERER']}");
        exit(0);
    });
    add_action('admin_post_crowdsec_reset_usage_metrics', function () {
        if (
            !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crowdsec_reset_usage_metrics')) {
            die('This link expired.');
        }
        resetBouncerMetricsInAdminPage();
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
                        $currentState = $onActivation($currentState);
                    }
                    if ($previousState && !$currentState) {
                        $currentState = $onDeactivation($currentState);
                    }
                }

                return $currentState;
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

        function addFieldString(
                string $optionName,
                string $label,
                string $optionGroup,
                string $pageName,
                string $sectionName,
                callable $onChange,
                $descriptionHtml,
                $placeholder,
                $inputStyle,
                $inputType = 'text',
                $disabled = false,
                $default = ''
        )
        {
            register_setting($optionGroup, $optionName, function ($input) use ($onChange, $optionName, $default) {
                $currentState = esc_attr($input);
                $previousState = is_multisite() ? esc_attr(get_site_option($optionName)) : esc_attr(get_option($optionName));

                if (empty($currentState) && !empty($default)) {
                    $currentState = $onChange($currentState, $default);
                }
                if ($previousState !== $currentState) {
                    $currentState = $onChange($currentState);
                }

                return $currentState;
            });
            add_settings_field($optionName, $label, function ($args) use ($descriptionHtml, $optionName, $inputStyle, $inputType, $disabled) {
                $name = $args['label_for'];
                $placeholder = $args['placeholder'];
                $value = $previousState = is_multisite() ? esc_attr(get_site_option($optionName)) : esc_attr(get_option($optionName));
                echo '<input  '.($disabled ? 'disabled="disabled"' : '')." 
                style=\"$inputStyle\" type=\"$inputType\" class=\"regular-text\" name=\"$name\" value=\"$value\" placeholder=\"$placeholder\">$descriptionHtml";
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
