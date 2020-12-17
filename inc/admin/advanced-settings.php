<?php

use CrowdSecBouncer\Constants;

function adminAdvancedSettings()
{
    /***************************
     ** Section "Stream mode" **
     **************************/

    add_settings_section('crowdsec_admin_advanced_stream_mode', 'Stream mode vs Live mode', function () {
        echo "
<p>With the stream mode, all decisions are retrieved in an asynchronous way, using <em>LAPI stream mode</em> feature
<br>Advantages:<br>- Ultrashort latency<br>- IP verifications work even if LAPI is down.<br>
Limits:<br>- If traffic is low, the cache refresh (new or deleted decisions since last time) can be late.
<br>- A delay to take decisions into account is added.
</p>";
    }, 'crowdsec_advanced_settings');

    // Field "crowdsec_stream_mode"

    addFieldCheckbox('crowdsec_stream_mode', 'Enable the "Stream" mode', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_stream_mode', function () {
        // Stream mode just activated.
        scheduleBlocklistRefresh();
    }, function () {
        // Stream mode just deactivated.
        unscheduleBlocklistRefresh();
    }, '');

    // Field "crowdsec_stream_mode_refresh_frequency"
    addFieldString('crowdsec_stream_mode_refresh_frequency', 'Resync decisions each', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        $input = (int)$input;
        if ($input < 60) {
            $input = 60;
            add_settings_error("Resync decisions each", "crowdsec_error", 'The "Resync decisions each" value should be more than 60sec (WP_CRON_LOCK_TIMEOUT). We just reset the frequency to 60 seconds.');
            return $input;
        }

        // Update wp-cron schedule.
        if ((bool)get_option("crowdsec_stream_mode")) {
            scheduleBlocklistRefresh();
        }
        return $input;
    }, ' seconds. <p>Our advice is 60 seconds (according to WP_CRON_LOCK_TIMEOUT).</p>', '...', 'width: 115px;', 'number');

    /*********************
     ** Section "Cache" **
     ********************/

    add_settings_section('crowdsec_admin_advanced_cache', 'Caching configuration', function () {
?>
        <p>The File system cache is faster than calling LAPI. Redis or Memcached is faster than the File System cache.</p>
        <p><input type="button" value="Clear the cache" class="button button-secondary button-small" onclick="if (confirm('Are you sure you want to completely clear the cache?')) document.getElementById('crowdsec_ation_clear_cache').submit();"></p>
    <?php
    }, 'crowdsec_advanced_settings');

    // Field "crowdsec_cache_system"
    add_settings_field('crowdsec_cache_system', 'Technology', function ($args) {
    ?>
        <select name="crowdsec_cache_system">
            <option value="<?php echo CROWDSEC_CACHE_SYSTEM_PHPFS ?>" <?php selected(get_option('crowdsec_cache_system'), CROWDSEC_CACHE_SYSTEM_PHPFS); ?>>File system</option>
            <option value="<?php echo CROWDSEC_CACHE_SYSTEM_REDIS ?>" <?php selected(get_option('crowdsec_cache_system'), CROWDSEC_CACHE_SYSTEM_REDIS); ?>>Redis</option>
            <option value="<?php echo CROWDSEC_CACHE_SYSTEM_MEMCACHED ?>" <?php selected(get_option('crowdsec_cache_system'), CROWDSEC_CACHE_SYSTEM_MEMCACHED); ?>>Memcached</option>
        </select>
    <?php
    }, 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', array(
        'label_for' => 'crowdsec_cache_system',
        'class' => 'ui-toggle'
    ));
    register_setting('crowdsec_plugin_advanced_settings', 'crowdsec_cache_system', function ($input) {
        $previousState = esc_attr(get_option("crowdsec_stream_mode_refresh_frequency"));
        $input = esc_attr($input);
        if (!in_array($input, [
            CROWDSEC_CACHE_SYSTEM_PHPFS,
            CROWDSEC_CACHE_SYSTEM_REDIS,
            CROWDSEC_CACHE_SYSTEM_MEMCACHED
        ])) {
            $input = CROWDSEC_CACHE_SYSTEM_PHPFS;
        }

        // On cache system change
        if ($previousState !== $input) {

            // Clear old cache before changing system (don't display message and don't warmup)
            clearBouncerCache(false, true);
            AdminNotice::displaySuccess('Cache system changed. Previous cache data has been cleared.');

            // Update wp-cron schedule if stream mode is enabled
            if ((bool)get_option("crowdsec_stream_mode")) {
                $bouncer = getBouncerInstance($input); // Reload bouncer instance with the new cache system
                $bouncer->warmBlocklistCacheUp();
                scheduleBlocklistRefresh();
            }
        }

        return $input;
    });

    // Field "crowdsec_redis_dsn"
    addFieldString('crowdsec_redis_dsn', 'Redis DSN<br>(if applicable)', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        // TODO P2 check if it's a valid DSN
        return $input;
    }, '<p>Fill in this field only if you have chosen the Redis cache.<br>Example of DSN: redis://localhost:6379.', 'redis://...', '');

    // Field "crowdsec_memcached_dsn"
    addFieldString('crowdsec_memcached_dsn', 'Memcached DSN<br>(if applicable)', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        // TODO P2 check if it's a valid DSN
        return $input;
    }, '<p>Fill in this field only if you have chosen the Memcached cache.<br>Example of DSN: memcached://localhost:11211.', 'memcached://...', '');

    // Field "crowdsec_captcha_technology"
    /*
    add_settings_field('crowdsec_captcha_technology', 'Captcha technology', function ($args) {
        ?>
            <select name="crowdsec_captcha_technology">
                <option value="<?php echo CROWDSEC_CAPTCHA_TECHNOLOGY_LOCAL ?>"
                <?php selected(get_option('crowdsec_captcha_technology'), CROWDSEC_CAPTCHA_TECHNOLOGY_LOCAL); ?>>Local</option>
                <option value="<?php echo CROWDSEC_CAPTCHA_TECHNOLOGY_RECAPTCHA ?>"
                <?php selected(get_option('crowdsec_captcha_technology'), CROWDSEC_CAPTCHA_TECHNOLOGY_RECAPTCHA); ?>>Recaptha</option>
            </select>
            <p>
                Local is the classical way to display a captcha. Recaptcha is the standard way.
            </p>
    <?php
        }, 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', array(
            'label_for' => 'crowdsec_captcha_technology',
            'class' => 'ui-toggle'
        ));
        register_setting('crowdsec_plugin_advanced_settings', 'crowdsec_captcha_technology', function ($input) {
            $input = esc_attr($input);
            if (!in_array($input, [
                CROWDSEC_CAPTCHA_TECHNOLOGY_LOCAL,
                CROWDSEC_CAPTCHA_TECHNOLOGY_RECAPTCHA
            ])) {
                $input = CROWDSEC_CACHE_SYSTEM_PHPFS;
            }
            return $input;
        });*/

    // Field "crowdsec_clean_ip_cache_duration"
    addFieldString('crowdsec_clean_ip_cache_duration', 'Recheck clean IPs each', 'crowdsec_plugin_advanced_settings', 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_cache', function ($input) {
        if ((int)$input <= 0) {
            add_settings_error("Recheck clean IPs each", "crowdsec_error", "Recheck clean IPs each: Minimum is 1 second.");
            return "1";
        }
        return $input;
    }, ' seconds. <p>The duration (in seconds) between re-asking LAPI about an already checked IP.<br>Minimum 1 second.', '...', 'width: 115px;', 'number');

    /***************************
     ** Section "Remediation" **
     **************************/

    add_settings_section('crowdsec_admin_advanced_remediations', 'Remediations', function () {
        echo "Configuration some details about remediations.";
    }, 'crowdsec_advanced_settings');

    // Field "crowdsec_fallback_remediation"
    add_settings_field('crowdsec_fallback_remediation', 'Fallback to', function ($args) {
    ?>
        <select name="crowdsec_fallback_remediation">
            <?php foreach (Constants::ORDERED_REMEDIATIONS as $remediation) : ?>
                <option value="<?php echo $remediation ?>" <?php selected(get_option('crowdsec_fallback_remediation'), $remediation); ?>><?php echo $remediation ?></option>
            <?php endforeach; ?>
        </select>
        <p>
            Which remediation to apply when CrowdSec advises unhandled remediation.<br>
        </p>
<?php
    }, 'crowdsec_advanced_settings', 'crowdsec_admin_advanced_remediations', array(
        'label_for' => 'crowdsec_fallback_remediation',
        'class' => 'ui-toggle'
    ));
    register_setting('crowdsec_plugin_advanced_settings', 'crowdsec_fallback_remediation', function ($input) {
        $input = esc_attr($input);
        if (!in_array($input, Constants::ORDERED_REMEDIATIONS)) {
            $input = Constants::REMEDIATION_CAPTCHA;
        }
        return $input;
    });
}
