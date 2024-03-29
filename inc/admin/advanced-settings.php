<?php

use CrowdSecWordPressBouncer\AdminNotice;
use CrowdSecWordPressBouncer\Constants;
use CrowdSecWordPressBouncer\Bouncer;
use CrowdSecBouncer\BouncerException;
use CrowdSec\RemediationEngine\Constants as RemConstants;
use IPLib\Factory;

require_once __DIR__ . '/../Constants.php';
require_once __DIR__ . '/../options-config.php';
require_once __DIR__ . '/notice.php';

function adminAdvancedSettings()
{
    if(is_multisite()){
        add_action('network_admin_edit_crowdsec_advanced_settings', 'crowdsec_multi_save_advanced_settings');
    }

    function crowdsec_multi_save_advanced_settings()
    {
        if (
            !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crowdsec-advanced-settings-update')) {
            wp_nonce_ays('crowdsec_save_advanced_settings');
        }

        $options =
            [
                'crowdsec_stream_mode',
                'crowdsec_stream_mode_refresh_frequency',
                'crowdsec_redis_dsn',
                'crowdsec_memcached_dsn',
                'crowdsec_cache_system',
                'crowdsec_clean_ip_cache_duration',
                'crowdsec_bad_ip_cache_duration',
                'crowdsec_captcha_cache_duration',
                'crowdsec_fallback_remediation',
                'crowdsec_trust_ip_forward_list',
                'crowdsec_hide_mentions',
                'crowdsec_geolocation_enabled',
                'crowdsec_geolocation_type',
                'crowdsec_geolocation_maxmind_database_type',
                'crowdsec_geolocation_maxmind_database_path',
                'crowdsec_geolocation_cache_duration',
                'crowdsec_debug_mode',
                'crowdsec_disable_prod_log',
                'crowdsec_custom_user_agent',
                'crowdsec_display_errors',
                'crowdsec_forced_test_ip',
                'crowdsec_forced_test_forwarded_ip',
                'crowdsec_auto_prepend_file_mode'
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
                    'page' => 'crowdsec_advanced_settings',
                    'updated' => true
                ),
                network_admin_url('admin.php')
            )
        );
        exit;
    }

    /***************************
     ** Section "Stream mode" **
     **************************/
    $streamMode = is_multisite() ? get_site_option('crowdsec_stream_mode') : get_option('crowdsec_stream_mode');
    add_settings_section('crowdsec_admin_advanced_stream_mode', 'Communication mode to the API', function () {
    }, 'crowdsec_advanced_settings');

    // Field "crowdsec_stream_mode"
    addFieldCheckbox('crowdsec_stream_mode', 'Enable the "Stream" mode', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_stream_mode', function () {
        // Stream mode just activated.
        $configs = getDatabaseConfigs();
        $configs['crowdsec_stream_mode'] = true;
        $bouncer = new Bouncer($configs);
        $bouncer->clearCache();
        $refresh = $bouncer->refreshBlocklistCache();
        $new = $refresh['new']??0;
        $deleted = $refresh['deleted']??0;
        $message = __('Settings saved.<br>As the stream mode is enabled, the cache has just been refreshed. New decision(s): '.$new.'. Deleted decision(s): '. $deleted);
        AdminNotice::displaySuccess($message);
        scheduleBlocklistRefresh();
    }, function () {
        // Stream mode just deactivated.
        unscheduleBlocklistRefresh();
    }, '
    <p>With the stream mode, every decision is retrieved in an asynchronous way. 3 advantages: <br>&nbsp;1) Inivisible latency when loading pages<br>&nbsp;2) The IP verifications works even if your CrowdSec is not reachable.<br>&nbsp;3) The API can never be overloaded by the WordPress traffic</p>
    <p>Note: This method has one limit: all the decisions updates since the previous resync will not be taken in account until the next resync.</p>'.
       ($streamMode ?
            '<p><input id="crowdsec_refresh_cache" style="margin-right:10px" type="button" value="Refresh the cache now" class="button button-secondary button-small" onclick="document.getElementById(\'crowdsec_action_refresh_cache\').submit();"></p>' :
            '<p><input id="crowdsec_refresh_cache" style="margin-right:10px" type="button" disabled="disabled" value="Refresh the cache now" class="button button-secondary button-small"></p>'));

    // Field "crowdsec_stream_mode_refresh_frequency"
    addFieldString('crowdsec_stream_mode_refresh_frequency', 'Resync decisions each<br>(stream mode only)', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_stream_mode', function ($input) {
        $input = (int) $input;
        if ($input < 1) {
            $input = 1;
            $message = 'The "Resync decisions each" value should be more than 1sec (WP_CRON_LOCK_TIMEOUT). We just reset the frequency to 1 seconds.';
            if(is_multisite()){
                AdminNotice::displayError($message);
            }else{
                add_settings_error('Resync decisions each', 'crowdsec_error', $message);
            }

            return $input;
        }
        $streamMode = is_multisite() ? get_site_option('crowdsec_stream_mode') : get_option('crowdsec_stream_mode');
        // Update wp-cron schedule.
        if ((bool) $streamMode) {
            $configs = getDatabaseConfigs();
            $configs['crowdsec_stream_mode'] = true;
            $bouncer = new Bouncer($configs);
            $bouncer->clearCache();
            $refresh = $bouncer->refreshBlocklistCache();
            $new = $refresh['new']??0;
            $deleted = $refresh['deleted']??0;
            $message = __('As the stream mode refresh duration changed, the cache has just been refreshed. New decision(s): '.$new.'. Deleted decision(s): '. $deleted);
            AdminNotice::displaySuccess($message);
            scheduleBlocklistRefresh();
        }

        return $input;
    }, ' seconds. <p>Our advice is 60 seconds (as WordPress ignores durations under this value <a href="https://wordpress.stackexchange.com/questions/100104/better-handling-of-wp-cron-server-load-abuse" target="_blank">see WP_CRON_LOCK_TIMEOUT</a>).<br>'.
    ' If you need a shorter delay between each resync, you can <strong>go down to 1 sec</strong>.<br>'.
    ' But as mentionned is the WordPress Developer Documentation, you should considere hooking WP-Cron Into the System Task Scheduler'.
    ' by yourself and reduce the WP_CRON_LOCK_TIMEOUT value to the same value as you set here. '.
    '<a href="https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/" target="_blank">'.
    'Here is explained how</a>.</p>', '...', 'width: 115px;', 'number');

    /*********************
     ** Section "Cache" **
     ********************/

    add_settings_section('crowdsec_admin_advanced_cache', 'Caching configuration <input id="crowdsec_clear_cache" style="margin-left: 7px;margin-top: -3px;" type="button" value="Clear now" class="button button-secondary button-small" onclick="if (confirm(\'Are you sure you want to completely clear the cache?\')) document.getElementById(\'crowdsec_action_clear_cache\').submit();">', function () {
        ?>
        <p>Polish the decisions cache settings by selecting the best technology or the cache durations best suited to your use.</p>
<?php
    }, 'crowdsec_advanced_settings');

    // Field "crowdsec_redis_dsn"
    addFieldString('crowdsec_redis_dsn', 'Redis DSN<br>(if applicable)', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        try {
            // Reload bouncer instance with the new cache system and so test if dsn is correct.
            $configs = getDatabaseConfigs();
            $oldDsn = $configs['crowdsec_redis_dsn'] ?? '';
            $configs['crowdsec_redis_dsn'] = $input;
            $bouncer = new Bouncer($configs);
            $bouncer->testCacheConnection();
        } catch (Exception $e) {
            $message = __('There was an error while testing new DSN ('.$input.')');
            if(isset($oldDsn)){
                AdminNotice::displayError($message.': '.$e->getMessage().'<br><br>Rollback to old DSN: '.$oldDsn);
                $input = $oldDsn;
            } else{
                AdminNotice::displayError($message.': '.$e->getMessage());
            }
        }
        return $input;
    }, '<p>Fill in this field only if you have chosen the Redis cache.<br>Example of DSN: redis://localhost:6379.', 'redis://...', '');

    // Field "crowdsec_memcached_dsn"
    addFieldString('crowdsec_memcached_dsn', 'Memcached DSN<br>(if applicable)', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        try {
            // Reload bouncer instance with the new cache system and so test if dsn is correct.
            $configs = getDatabaseConfigs();
            $oldDsn = $configs['crowdsec_memcached_dsn'] ?? '';
            $configs['crowdsec_memcached_dsn'] = $input;
            $bouncer = new Bouncer($configs);
            $bouncer->testCacheConnection();
        } catch (Exception $e) {
            $message = __('There was an error while testing new DSN ('.$input.')');
            if(isset($oldDsn)){
                AdminNotice::displayError($message.': '.$e->getMessage().'<br><br>Rollback to old DSN: '.$oldDsn);
                $input = $oldDsn;
            } else{
                AdminNotice::displayError($message.': '.$e->getMessage());
            }
        }
        return $input;
    }, '<p>Fill in this field only if you have chosen the Memcached cache.<br>Example of DSN: memcached://localhost:11211.', 'memcached://...', '');

    // Field "crowdsec_cache_system"
    $cacheSystem = is_multisite() ? get_site_option('crowdsec_cache_system') : get_option('crowdsec_cache_system');
    addFieldSelect('crowdsec_cache_system', 'Technology', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        if (!in_array($input, [Constants::CACHE_SYSTEM_PHPFS, Constants::CACHE_SYSTEM_REDIS, Constants::CACHE_SYSTEM_MEMCACHED])) {
            $input = Constants::CACHE_SYSTEM_PHPFS;
            $message = 'Technology: Incorrect cache technology selected.';
            if(is_multisite()){
                AdminNotice::displayError($message);
            }else{
                add_settings_error('Technology', 'crowdsec_error', $message);

            }
        }
        $error = false;
        $message = '';

        try {
            $configs = getDatabaseConfigs();
            $oldCacheSystem = $configs['crowdsec_cache_system'] ?? Constants::CACHE_SYSTEM_PHPFS;
            $bouncer = new Bouncer($configs);
            $bouncer->clearCache();
            $message =
                __('Cache system changed. Previous cache (' . $oldCacheSystem . ') data has been cleared. ');
            AdminNotice::displaySuccess($message);
        } catch (Exception $e) {
            if (isset($configs['crowdsec_cache_system'])) {
                $message = __('Cache system changed but there was an error while clearing previous cache (' .
                              $configs['crowdsec_cache_system'] . '). ');
                AdminNotice::displayWarning($message . ': ' . $e->getMessage());
            } else {
                AdminNotice::displayError($e->getMessage());
            }
        }

        try {
            // Reload bouncer instance with the new cache system and so test if dsn is correct.
            $configs['crowdsec_cache_system'] = $input;
            $bouncer = new Bouncer($configs);
            $bouncer->testCacheConnection();
        } catch (Exception $e) {
            $message = __('There was an error while testing new cache ('.$input.')');
            $messageSuffix = isset($oldCacheSystem) ? __('<br><br> Rollback to previous cache: '.$oldCacheSystem) : '';
            AdminNotice::displayError($message.': '.$e->getMessage().$messageSuffix);
            if(isset($oldCacheSystem)){
                $input = $oldCacheSystem;
            }
            $error = true;
        }

        try {
            $streamMode = is_multisite() ? get_site_option('crowdsec_stream_mode') : get_option('crowdsec_stream_mode');
            if ($streamMode && !$error) {
                // system
                $bouncer->clearCache();
                $result = $bouncer->refreshBlocklistCache();
                $new = $result['new']??0;
                $deleted = $result['deleted']??0;
                $message = __('Settings saved.<br>As the stream mode is enabled, the cache has just been refreshed. New decision(s): '.$new.'. Deleted decision(s): '. $deleted);
                AdminNotice::displaySuccess($message);
                scheduleBlocklistRefresh();
            }
        } catch (Exception $e) {
            AdminNotice::displayError($e->getMessage());
        }

        return $input;
    }, ((Constants::CACHE_SYSTEM_PHPFS === $cacheSystem) ?
        '<input style="margin-right:10px" type="button" id="crowdsec_prune_cache" value="Prune now" class="button button-secondary" onclick="document.getElementById(\'crowdsec_action_prune_cache\').submit();">' : '').
        '<p>The File system cache is faster than calling Local API. Redis or Memcached is faster than the File System cache.<br>
<b>Important note: </b> If you use the File system cache, make sure the <i>wp-content/uploads/crowdsec/cache</i> path is not publicly accessible.<br>
Please refer to <a target="_blank" href="https://github.com/crowdsecurity/cs-wordpress-bouncer/blob/main/docs/USER_GUIDE.md#security">the documentation to deny direct access to this folder.</a></p>', [
        Constants::CACHE_SYSTEM_PHPFS => 'File system',
        Constants::CACHE_SYSTEM_REDIS => 'Redis',
        Constants::CACHE_SYSTEM_MEMCACHED => 'Memcached',
    ]);

    // Field "crowdsec_clean_ip_cache_duration"
    addFieldString('crowdsec_clean_ip_cache_duration', 'Recheck clean IPs each<br>(live mode only)', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        if(!empty($input)){
            $streamMode = is_multisite() ? get_site_option('crowdsec_stream_mode') : get_option('crowdsec_stream_mode');
            if (!$streamMode && (int) $input <= 0) {
                $message = 'Recheck clean IPs each: Minimum is 1 second.';
                if(is_multisite()){
                    AdminNotice::displayError($message);
                }else{
                    add_settings_error($message);

                }

                return '1';
            }

            return (int) $input > 0 ? (int) $input : 1 ;
        }
        $saved = is_multisite() ? (int) get_site_option('crowdsec_clean_ip_cache_duration') : (int) get_option('crowdsec_clean_ip_cache_duration');
        return $saved > 0 ? $saved : 1;

    }, ' seconds. <p>The duration between re-asking Local API about an already checked clean IP.<br>Minimum 1 second.<br> Note that this setting can not be apply in stream mode.', '...', 'width: 115px;', 'number', (bool) $streamMode);

    // Field "crowdsec_bad_ip_cache_duration"
    addFieldString('crowdsec_bad_ip_cache_duration', 'Recheck bad IPs each<br>(live mode only)', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        if(!empty($input)) {
            $streamMode = is_multisite() ? get_site_option('crowdsec_stream_mode') : get_option('crowdsec_stream_mode');
            if (!$streamMode && !empty($input) && (int)$input <= 0) {
                $message = 'Recheck bad IPs each: Minimum is 1 second.';
                if(is_multisite()){
                    AdminNotice::displayError($message);
                }else{
                    add_settings_error($message);

                }

                return '1';
            }

            return (int)$input > 0 ? (int)$input : 1;
        }
        $saved = is_multisite() ? (int) get_site_option('crowdsec_bad_ip_cache_duration') :  (int) get_option('crowdsec_bad_ip_cache_duration');
        return $saved > 0 ? $saved : 1;

    }, ' seconds. <p>The duration between re-asking Local API about an already checked bad IP.<br>Minimum 1 second.<br> Note that this setting can not be apply in stream mode.', '...', 'width: 115px;', 'number', (bool) $streamMode);

    // Field "crowdsec_captcha_cache_duration"
    addFieldString('crowdsec_captcha_cache_duration', 'Captcha flow cache lifetime', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        if ( (int) $input <= 0) {
            $message = 'Captcha cache duration: Minimum is 1 second.';
            if(is_multisite()){
                AdminNotice::displayError($message);
            }else{
                add_settings_error($message);

            }

            return Constants::CACHE_EXPIRATION_FOR_CAPTCHA;
        }

        return (int) $input;
    }, ' seconds. <p>The lifetime of cached captcha flow for some IP. <br>If a user has to interact with a captcha wall, we store in cache some values in order to know if he has to resolve or not the captcha again.<br>Minimum 1 second. Default: '.Constants::CACHE_EXPIRATION_FOR_CAPTCHA.'.', Constants::CACHE_EXPIRATION_FOR_CAPTCHA, 'width: 115px;', 'number');


    /***************************
     ** Section "Remediation" **
     **************************/

    add_settings_section('crowdsec_admin_advanced_remediations', 'Remediations', function () {
        echo 'Configure some details about remediations.';
    }, 'crowdsec_advanced_settings');

    // Field "crowdsec_fallback_remediation"
    $choice = [];
    $remediations = [Constants::REMEDIATION_BAN, Constants::REMEDIATION_CAPTCHA, Constants::REMEDIATION_BYPASS];
    foreach ($remediations as $remediation) {
        $choice[$remediation] = $remediation;
    }
    addFieldSelect('crowdsec_fallback_remediation', 'Fallback to', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings',
    'crowdsec_admin_advanced_remediations', function ($input) {
        $remediations = [Constants::REMEDIATION_BAN, Constants::REMEDIATION_CAPTCHA, Constants::REMEDIATION_BYPASS];
        if (!in_array($input, $remediations)) {
            $input = Constants::BOUNCING_LEVEL_DISABLED;
            $message = 'Fallback to: Incorrect Fallback selected.';
            if(is_multisite()){
                AdminNotice::displayError($message);
            }else{
                add_settings_error('Fallback to', 'crowdsec_error', $message);
            }
        }

        return $input;
    }, '<p>Which remediation to apply when CrowdSec advises unhandled remediation.</p>', $choice);

    function convertInlineIpRangesToComparableIpBounds(string $inlineIpRanges): array
    {
        $comparableIpBoundsList = [];
        $stringRangeArray = explode(',', $inlineIpRanges);
        foreach ($stringRangeArray as $stringRange) {
            $stringRange = trim($stringRange);
            if (false !== strpos($stringRange, '/')) {
                $range = Factory::parseRangeString($stringRange);
                if (null === $range) {
                    throw new BouncerException('Invalid IP List format.');
                }
                $bounds = [$range->getComparableStartString(), $range->getComparableEndString()];
                $comparableIpBoundsList = array_merge($comparableIpBoundsList, [$bounds]);
            } else {
                $address = Factory::parseAddressString($stringRange, 3);
                if (null === $address) {
                    throw new BouncerException('Invalid IP List format.');
                }
                $comparableString = $address->getComparableString();
                $comparableIpBoundsList = array_merge($comparableIpBoundsList, [[$comparableString, $comparableString]]);
            }
        }

        return $comparableIpBoundsList;
    }

    // Field "crowdsec_trust_ip_forward"
    addFieldString('crowdsec_trust_ip_forward_list', 'Trust these CDN IPs<br>(or Load Balancer, HTTP Proxy)', 'crowdsec_plugin_advanced_settings',
    'crowdsec_advanced_settings', 'crowdsec_admin_advanced_remediations', function ($input) {
        try {
            if ('' === $input) {
                if(is_multisite()){
                    update_site_option('crowdsec_trust_ip_forward_array', []);
                }else{
                    update_option('crowdsec_trust_ip_forward_array', []);
                }

                return $input;
            }
            $comparableIpBoundsList = convertInlineIpRangesToComparableIpBounds($input);
            if(is_multisite()){
                update_site_option('crowdsec_trust_ip_forward_array', $comparableIpBoundsList);
            }else{
                update_option('crowdsec_trust_ip_forward_array', $comparableIpBoundsList);
            }

            AdminNotice::displaySuccess('IPs with X-Forwarded-For to trust successfully saved.');
        } catch (BouncerException $e) {
            if(is_multisite()){
                update_site_option('crowdsec_trust_ip_forward_array', []);
            }else{
                update_option('crowdsec_trust_ip_forward_array', []);
            }

            $message = 'Trust these CDN IPs: Invalid IP List format.';
            if(is_multisite()){
                AdminNotice::displayError($message);
            }else{
                add_settings_error('Trust these CDN IPs', 'crowdsec_error', $message);

            }

            return '';
        }

        return $input;
    }, '<p>The <em><a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Forwarded-For" '.
    'target="_blank">X-forwarded-For</a></em> HTTP Header will be trust only when the client IP is in this list.'.
    '<br><strong>Comma (,)</strong> separated ips or ips ranges. Example: 1.2.3.4/24, 2.3.4.5, 3.4.5.6/27.<br><br>Some common CDN IP list: <a href="https://www.cloudflare.com/fr-fr/ips/" target="_blank">Cloudflare</a>, <a href="https://api.fastly.com/public-ip-list" target="_blank">Fastly</a>',
    'fill the IPs or IPs ranges here...', '');

    // Field "crowdsec_hide_mentions"
    addFieldCheckbox('crowdsec_hide_mentions', 'Hide CrowdSec mentions', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_remediations', function () {}, function () {}, '
    <p>Enable if you want to hide CrowdSec mentions on the Ban and Captcha pages</p>');

    /***************************
     ** Section "Geolocation" **
     **************************/

    add_settings_section('crowdsec_admin_advanced_geolocation', 'Geolocation', function () {
        echo 'Configure some details about geolocation.<br>
<b>Important note: </b> If you use this feature, make sure the geolocation database is not publicly accessible.<br>
Please refer to <a target="_blank" href="https://github.com/crowdsecurity/cs-wordpress-bouncer/blob/main/docs/USER_GUIDE.md#security">the documentation to deny direct access to this folder.</a>';
    }, 'crowdsec_advanced_settings');

    // Field "Geolocation enabled"
    addFieldCheckbox('crowdsec_geolocation_enabled', 'Enable geolocation feature', 'crowdsec_plugin_advanced_settings',
        'crowdsec_advanced_settings', 'crowdsec_admin_advanced_geolocation', function () {}, function () {}, '
    <p>Enable if you want to use also CrowdSec country scoped decisions.<br>If enabled, bounced IP will be geolocalized and the final remediation will take into account any country related decision.</p>');

    $geolocationTypes = [Constants::GEOLOCATION_TYPE_MAXMIND => 'MaxMind database' ];
    addFieldSelect('crowdsec_geolocation_type', 'Geolocation type', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings',
        'crowdsec_admin_advanced_geolocation', function ($input) {
            if ($input !== Constants::GEOLOCATION_TYPE_MAXMIND) {
                $input = Constants::GEOLOCATION_TYPE_MAXMIND;
                $message = 'Geolocation type: Incorrect geolocation type selected.';
                if(is_multisite()){
                    AdminNotice::displayError($message);
                }else{
                    add_settings_error('Geolocation type', 'crowdsec_error', $message);
                }
            }

            return $input;
        }, '<p>For now, only Maxmind database type is allowed</p>', $geolocationTypes);

    $maxmindDatabaseTypes = [Constants::MAXMIND_COUNTRY => 'Country', RemConstants::MAXMIND_CITY => 'City'];
    addFieldSelect('crowdsec_geolocation_maxmind_database_type', 'MaxMind database type', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings',
        'crowdsec_admin_advanced_geolocation', function ($input) {
            if (!in_array($input, [Constants::MAXMIND_COUNTRY, RemConstants::MAXMIND_CITY])) {
                $input = Constants::MAXMIND_COUNTRY;
                $message = 'MaxMind database type: Incorrect type selected.';
                if(is_multisite()){
                    AdminNotice::displayError($message);
                }else{
                    add_settings_error('Geolocation MaxMind database type', 'crowdsec_error', $message);
                }
            }

            return $input;
        }, '<p></p>', $maxmindDatabaseTypes);

    addFieldString('crowdsec_geolocation_maxmind_database_path', 'Path to the MaxMind database', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_geolocation', function ($input) {
        return $input;
    }, '<p>Absolute path</p>', '/var/crowdsec/geolocation/GeoLite2-Country.mmdb', '');

    // Field "crowdsec_geolocation_cache_duration"
    addFieldString('crowdsec_geolocation_cache_duration', 'Geolocation cache lifetime', 'crowdsec_plugin_advanced_settings',
        'crowdsec_advanced_settings', 'crowdsec_admin_advanced_geolocation', function ($input) {
            if ( (int) $input < 0) {
                $message = 'Geolocation cache duration: Minimum is 0 second.';
                if(is_multisite()){
                    AdminNotice::displayError($message);
                }else{
                    add_settings_error('Geolocation cache duration', 'crowdsec_error', $message);
                }

                return Constants::CACHE_EXPIRATION_FOR_GEO;
            }

            return (int) $input;
        }, ' seconds. <p>The lifetime of cached country geolocation result for some IP.<br>Default: '
           .Constants::CACHE_EXPIRATION_FOR_GEO.'.<br>Set 0 to disable caching', Constants::CACHE_EXPIRATION_FOR_GEO,
        'width: 115px;', 'number');


    /*******************************
     ** Section "Debug mode" **
     ******************************/

    add_settings_section('crowdsec_admin_advanced_debug', 'Debug mode', function () {
        echo 'Configure the debug mode.<br>
<b>Important note: </b> Make sure the <i>wp-content/uploads/crowdsec/logs</i> path is not publicly accessible.<br>
Please refer to <a target="_blank" href="https://github.com/crowdsecurity/cs-wordpress-bouncer/blob/main/docs/USER_GUIDE.md#security">the documentation to deny direct access to this folder.</a>';
    }, 'crowdsec_advanced_settings');

    // Field "crowdsec_debug_mode"
    addFieldCheckbox('crowdsec_debug_mode', 'Enable debug mode', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_debug', function () {}, function () {}, '
    <p>Should not be used in production.<br>When this mode is enabled, a <i>debug.log</i> file will be written in the <i>wp-content/uploads/crowdsec/logs</i> folder.</p>');

    // Field "crowdsec_disable_prod_log"
    addFieldCheckbox('crowdsec_disable_prod_log', 'Disable prod log', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_debug', function () {}, function () {}, '
    <p>By default, a <i>prod.log</i> file is written in the <i>wp-content/uploads/crowdsec/logs</i> folder.<br>You can disable this log here.</p>');

    // Field "Custom User Agent"
    addFieldString('crowdsec_custom_user_agent', 'Custom User-Agent', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_debug', function ($input) {
        if ( 1 !== preg_match('#^[A-Za-z0-9]{0,5}$#', $input)) {
            $message = 'Custom User-Agent: Only alphanumeric characters ([A-Za-z0-9]) are allowed with a maximum of 5 characters.';
            if(is_multisite()){
                AdminNotice::displayError($message);
            }else{
                add_settings_error('Custom User-Agent', 'crowdsec_error', $message);
            }

            return '';
        }

        return $input;
    }, '<p>By default, User-Agent used to call LAPI has the following format: <i>csphplapi_WordPress</i>.<br>You can use this field to add a custom suffix: <i>csphplapi_WordPress<b>[custom-suffix]</b></i>.<br>Only alphanumeric characters ([A-Za-z0-9]) are allowed with a maximum of 5 characters.</p>',
        'Site1', 'max-width:100px;');

	/*******************************
	 ** Section "Display errors" **
	 ******************************/

	add_settings_section('crowdsec_admin_advanced_display_errors', 'Display errors', function () {
		echo 'Configure the errors display.';
	}, 'crowdsec_advanced_settings');

	// Field "crowdsec_display_errors"
	addFieldCheckbox('crowdsec_display_errors', 'Enable errors display', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_display_errors', function () {}, function () {}, '
    <p><strong>Do not use in production.</strong> When this mode is enabled, you will see every unexpected bouncing errors in the browser.</p>');


    /*******************************
     ** Section "Auto prepend file mode" **
     ******************************/

    add_settings_section('crowdsec_admin_advanced_auto_prepend_file_mode', 'Auto prepend file mode', function () {
        echo '';
    }, 'crowdsec_advanced_settings');

    // Field "crowdsec_standalone_mode"
    addFieldCheckbox('crowdsec_auto_prepend_file_mode', 'Enable auto_prepend_file mode', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_auto_prepend_file_mode', function () {}, function () {}, '
    <p>This setting allows the bouncer to bounce IPs before running any PHP script in the project. <a href="https://github.com/crowdsecurity/cs-wordpress-bouncer/blob/main/docs/USER_GUIDE.md#auto-prepend-file-mode" target="_blank">Discover how to setup with this guide</a>.</p><p>Enable this option <b>before</b> adding the "<em>auto_prepend_file</em>" directive for your PHP setup.</p>');


    /*******************************
     ** Section "Test mode" **
     ******************************/

    add_settings_section('crowdsec_admin_advanced_test', 'Test settings', function () {
        echo 'Configure some test parameters.';
    }, 'crowdsec_advanced_settings');

    // Field "test ip"
    addFieldString('crowdsec_forced_test_ip', 'Forced test IP', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_test', function ($input) {
        return $input;
    }, '<p>This Ip will be used instead of the current detected browser IP: '.$_SERVER['REMOTE_ADDR'].'.<br><strong>Must be empty in production.</strong></p>',
    '1.2.3.4', '');

    addFieldString('crowdsec_forced_test_forwarded_ip', 'Forced test X-Forwarded-For IP', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_test', function ($input) {
        return $input;
    }, '<p>This Ip will be used instead of the current X-Forwarded-For Ip if any.<br><strong>Must be empty in production.</strong></p>',
        '1.2.3.4', '');


}
