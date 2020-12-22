<?php

use CrowdSecBouncer\BouncerException;
use CrowdSecBouncer\Constants;

function adminAdvancedSettings()
{
    /***************************
     ** Section "Stream mode" **
     **************************/

    add_settings_section('crowdsec_admin_advanced_stream_mode', 'Communication mode to the API', function () {
    }, 'crowdsec_advanced_settings');

    // Field "crowdsec_stream_mode"
    addFieldCheckbox('crowdsec_stream_mode', 'Enable the "Stream" mode', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_stream_mode', function () {
        // Stream mode just activated.
        scheduleBlocklistRefresh();
    }, function () {
        // Stream mode just deactivated.
        unscheduleBlocklistRefresh();
    }, '
    <p>With the stream mode, every decision is retrieved in an asynchronous way. 3 advantages: <br>&nbsp;1) Inivisible latency when loading pages<br>&nbsp;2) The IP verifications works even if your CrowdSec is not reachable.<br>&nbsp;3) The API can never be overloaded by the WordPress traffic</p>
    <p>Note: This method has one limit: for maximum 60 seconds, all the new decisions may not be taken into account.</p>'.
        (get_option('crowdsec_stream_mode') ?
            '<p><input style="margin-right:10px" type="button" value="Refresh the cache now" class="button button-secondary button-small" onclick="document.getElementById(\'crowdsec_ation_refresh_cache\').submit();"></p>' :
            '<p><input style="margin-right:10px" type="button" disabled="disabled" value="Refresh the cache now" class="button button-secondary button-small"></p>'));

    // Field "crowdsec_stream_mode_refresh_frequency"
    addFieldString('crowdsec_stream_mode_refresh_frequency', 'Resync decisions each<br>(stream mode only)', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_stream_mode', function ($input) {
        $input = (int) $input;
        if ($input < 60) {
            $input = 60;
            add_settings_error('Resync decisions each', 'crowdsec_error', 'The "Resync decisions each" value should be more than 60sec (WP_CRON_LOCK_TIMEOUT). We just reset the frequency to 60 seconds.');

            return $input;
        }

        // Update wp-cron schedule.
        if ((bool) get_option('crowdsec_stream_mode')) {
            scheduleBlocklistRefresh();
        }

        return $input;
    }, ' seconds. <p>Our advice is 60 seconds (according to WP_CRON_LOCK_TIMEOUT).</p>', '...', 'width: 115px;', 'number');

    /*********************
     ** Section "Cache" **
     ********************/

    add_settings_section('crowdsec_admin_advanced_cache', 'Caching configuration <input style="margin-left: 7px;margin-top: -3px;" type="button" value="Clear now" class="button button-secondary button-small" onclick="if (confirm(\'Are you sure you want to completely clear the cache?\')) document.getElementById(\'crowdsec_ation_clear_cache\').submit();">', function () {
        ?>
        <p>Polish the decisions cache settings by selecting the best technology or the cache durations best suited to your use.</p>
<?php
    }, 'crowdsec_advanced_settings');

    // Field "crowdsec_redis_dsn"
    addFieldString('crowdsec_redis_dsn', 'Redis DSN<br>(if applicable)', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        // TODO P1 block remove if cache set to redis
        // TODO P1 Display an error in all sections (settings and advanced) when a cache config can not work.
        return $input;
    }, '<p>Fill in this field only if you have chosen the Redis cache.<br>Example of DSN: redis://localhost:6379.', 'redis://...', '');

    // Field "crowdsec_memcached_dsn"
    addFieldString('crowdsec_memcached_dsn', 'Memcached DSN<br>(if applicable)', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        // TODO P1 block remove if cache set to memcached
        // TODO P1 Display an error in all sections (settings and advanced) when a cache config can not work.
        return $input;
    }, '<p>Fill in this field only if you have chosen the Memcached cache.<br>Example of DSN: memcached://localhost:11211.', 'memcached://...', '');

    // Field "crowdsec_cache_system"
    addFieldSelect('crowdsec_cache_system', 'Technology', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        if (!in_array($input, [CROWDSEC_CACHE_SYSTEM_PHPFS, CROWDSEC_CACHE_SYSTEM_REDIS, CROWDSEC_CACHE_SYSTEM_MEMCACHED])) {
            $input = CROWDSEC_CACHE_SYSTEM_PHPFS;
            add_settings_error('Technology', 'crowdsec_error', 'Technology: Incorrect cache technology selected.');
        }

        try {
            $bouncer = getBouncerInstance();
            try {
                $bouncer->clearCache();
            } catch (BouncerException $e) {
                $cacheSystem = esc_attr(get_option('crowdsec_cache_system'));
                switch ($cacheSystem) {
                    case CROWDSEC_CACHE_SYSTEM_MEMCACHED:
                        throw new WordpressCrowdSecBouncerException('Unable to connect Memcached.'.
                            ' Please fix the Memcached DSN or select another cache technology.');
                        break;

                    case CROWDSEC_CACHE_SYSTEM_REDIS:
                        throw new WordpressCrowdSecBouncerException('Unable to connect Redis.'.
                            ' Please fix the Redis DSN or select another cache technology.');
                    default:
                    throw new WordpressCrowdSecBouncerException('Unable to connect the cache system: '.$e->getMessage());
                }
            }

            $message = __('Cache system changed. Previous cache data has been cleared.');
        } catch (WordpressCrowdSecBouncerException $e) {
        }

        try {
            // Reload bouncer instance with the new cache system and so test if dsn is correct.
            getCacheAdapterInstance($input);
            try {
                // Try the adapter connection (Redis or Memcached will crash if the connection is incorrect)
                $bouncer = getBouncerInstance($input);
                $bouncer->testConnection();
            } catch (BouncerException $e) {
                throw new WordpressCrowdSecBouncerException($e->getMessage());
            }
        } catch (WordpressCrowdSecBouncerException $e) {
            AdminNotice::displayError($e->getMessage());
        }

        try {
            try {
                //Update wp-cron schedule if stream mode is enabled
                if ((bool) get_option('crowdsec_stream_mode')) {
                    $bouncer = getBouncerInstance($input); // Reload bouncer instance with the new cache system
                    $result = $bouncer->warmBlocklistCacheUp();
                    $message = __('As the stream mode is enabled, the cache has just been warmed up, '.($result > 0 ? 'there are now '.$result.' decisions' : 'there is now '.$result.' decision').' in cache.');
                    AdminNotice::displaySuccess($message);
                    scheduleBlocklistRefresh();
                }
            } catch (WordpressCrowdSecBouncerException $e) {
                AdminNotice::displayError($e->getMessage());
            }
        } catch (BouncerException $e) {
            AdminNotice::displayError($e->getMessage());
        }

        return $input;
    }, ((CROWDSEC_CACHE_SYSTEM_PHPFS === get_option('crowdsec_cache_system')) ?
        '<input style="margin-right:10px" type="button" value="Prune now" class="button button-secondary" onclick="document.getElementById(\'crowdsec_ation_prune_cache\').submit();">' : '').
        '<p>The File system cache is faster than calling LAPI. Redis or Memcached is faster than the File System cache.</p>', [
        CROWDSEC_CACHE_SYSTEM_PHPFS => 'File system',
        CROWDSEC_CACHE_SYSTEM_REDIS => 'Redis',
        CROWDSEC_CACHE_SYSTEM_MEMCACHED => 'Memcached',
    ]);

    // Field "crowdsec_clean_ip_cache_duration"
    addFieldString('crowdsec_clean_ip_cache_duration', 'Recheck clean IPs each', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        if ((int) $input <= 0) {
            add_settings_error('Recheck clean IPs each', 'crowdsec_error', 'Recheck clean IPs each: Minimum is 1 second.');

            return '1';
        }

        return $input;
    }, ' seconds. <p>The duration (in seconds) between re-asking LAPI about an already checked clean IP.<br>Minimum 1 second.', '...', 'width: 115px;', 'number');

    // Field "crowdsec_bad_ip_cache_duration"
    addFieldString('crowdsec_bad_ip_cache_duration', 'Recheck bad IPs each', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        if ((int) $input <= 0) {
            add_settings_error('Recheck bad IPs each', 'crowdsec_error', 'Recheck bad IPs each: Minimum is 1 second.');

            return '1';
        }

        return $input;
    }, ' seconds. <p>The duration (in seconds) between re-asking LAPI about an already checked bad IP.<br>Minimum 1 second.', '...', 'width: 115px;', 'number');

    /***************************
     ** Section "Remediation" **
     **************************/

    add_settings_section('crowdsec_admin_advanced_remediations', 'Remediations', function () {
        echo 'Configuration some details about remediations.';
    }, 'crowdsec_advanced_settings');

    // Field "crowdsec_fallback_remediation"
    $choice = [];
    foreach (Constants::ORDERED_REMEDIATIONS as $remediation) {
        $choice[$remediation] = $remediation;
    }
    addFieldSelect('crowdsec_fallback_remediation', 'Fallback to', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_remediations', function ($input) {
        if (!in_array($input, Constants::ORDERED_REMEDIATIONS)) {
            $input = CROWDSEC_BOUNCING_LEVEL_DISABLED;
            add_settings_error('Fallback to', 'crowdsec_error', 'Fallback to: Incorrect Fallback selected.');
        }

        return $input;
    }, '<p>Which remediation to apply when CrowdSec advises unhandled remediation.</p>', $choice);

    // Field "crowdsec_hide_mentions"
    addFieldCheckbox('crowdsec_hide_mentions', 'Hide CrowdSec mentions', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_remediations', function () {
        // Stream mode just activated.
        scheduleBlocklistRefresh();
    }, function () {
        // Stream mode just deactivated.
        unscheduleBlocklistRefresh();
    }, '
    <p>Enable if you want to hide CrowdSec mentions on the Ban and Captcha pages</p>');
}
