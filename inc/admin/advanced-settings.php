<?php

use CrowdSecBouncer\Constants;

function adminAdvancedSettings()
{
    /**************************************
     ** Section "Advanced Configuration" **
     *************************************/

    add_settings_section('crowdsec_admin_advanced', 'Cache configuration', function () {
        echo "Leave these parameters as they are, except in special cases. ";
    }, 'crowdsec_advanced_settings');

    // Field "crowdsec_stream_mode"
    add_settings_field('crowdsec_stream_mode', 'Enable the "Stream" mode', function ($args) {
        $name = $args['label_for'];
        $classes = $args['class'];
        $checkbox = esc_attr(get_option($name));
        $options = esc_attr(get_option('crowdsec_stream_mode'));
        echo '<div class="' . $classes . '">' .
            '<input type="checkbox" id="' . $name . '" name="' . $name . '" value="' . $options . '"' .
            ' class="" ' . ($checkbox ? 'checked' : '') . '>' .
            '<label for="' . $name . '"><div></div></label></div>' .
            '<p>With this mode, all decisions are retrieved in an asynchronous way, using <em>LAPI stream mode</em> feature.' .
            '<br>Advantages:<br>- Ultrashort latency<br>- IP verifications work even if LAPI is down.<br>' .
            'Limits:<br>- If traffic is low, the cache refresh of all decisions can be late.' .
            '<br>- A delay to take decisions into account is added.' .
            '</p>';
    }, 'crowdsec_advanced_settings', 'crowdsec_admin_advanced', array(
        'label_for' => 'crowdsec_stream_mode',
        'class' => 'ui-toggle'
    ));
    register_setting('crowdsec_plugin_advanced_settings', 'crowdsec_stream_mode', function ($input) {
        $previousState = esc_attr(get_option("crowdsec_stream_mode"));
        $streamModeEnabled = sanitizeCheckbox($input);

        // Stream mode just activated.
        if (!$previousState && $streamModeEnabled) {
            scheduleBlocklistRefresh();
        }

        // Stream mode just deactivated.
        if ($previousState && !$streamModeEnabled) {
            unscheduleBlocklistRefresh();
        }

        return $streamModeEnabled;
    });

    // Field "crowdsec_stream_mode_refresh_frequency"
    add_settings_field('crowdsec_stream_mode_refresh_frequency', 'Resync decisions each', function ($args) {
        $name = $args["label_for"];
        $placeholder = $args["placeholder"];
        $value = esc_attr(get_option("crowdsec_stream_mode_refresh_frequency"));

        if (false) { // TODO check if its number
            echo "Incorrect ... " . $value . ".\n";
        }
        echo '<input style="width: 115px;" type="number" class="regular-text" name="' . $name . '"' .
            ' value="' . $value . '" placeholder="' . $placeholder . '"> seconds.' .
            '<p>Our advice is 60sec (according to WP_CRON_LOCK_TIMEOUT)';
    }, 'crowdsec_advanced_settings', 'crowdsec_admin_advanced', array(
        'label_for' => 'crowdsec_stream_mode_refresh_frequency',
        'placeholder' => '...',
    ));
    register_setting('crowdsec_plugin_advanced_settings', 'crowdsec_stream_mode_refresh_frequency', function ($input) {
        $previousState = (int)(get_option("crowdsec_stream_mode_refresh_frequency"));
        $input = (int)$input;
        if ($input < 60) {
            $input = 60;
            add_settings_error("Resync decisions each", "crowdsec_error", 'The "Resync decisions each" value should be more than 60sec (WP_CRON_LOCK_TIMEOUT). We just reset the frequency to 60sec.');
            return $input;
        }

        // Update wp-cron schedule.
        if (($previousState !== $input) && (bool)(get_option("crowdsec_stream_mode"))) {
            scheduleBlocklistRefresh();
        }
        return $input;
    });

    // Field "crowdsec_cache_system"
    add_settings_field('crowdsec_cache_system', 'Caching technology', function ($args) {
?>
        <select name="crowdsec_cache_system">
            <option value="<?php echo CROWDSEC_CACHE_SYSTEM_PHPFS ?>" <?php selected(get_option('crowdsec_cache_system'), CROWDSEC_CACHE_SYSTEM_PHPFS); ?>>File system</option>
            <option value="<?php echo CROWDSEC_CACHE_SYSTEM_REDIS ?>" <?php selected(get_option('crowdsec_cache_system'), CROWDSEC_CACHE_SYSTEM_REDIS); ?>>Redis</option>
            <option value="<?php echo CROWDSEC_CACHE_SYSTEM_MEMCACHED ?>" <?php selected(get_option('crowdsec_cache_system'), CROWDSEC_CACHE_SYSTEM_MEMCACHED); ?>>Memcached</option>
        </select>
        <p>
            The File system cache is faster than calling LAPI. Redis or Memcached is faster than the File System cache.<br>
        </p>
    <?php
    }, 'crowdsec_advanced_settings', 'crowdsec_admin_advanced', array(
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
    add_settings_field('crowdsec_redis_dsn', 'Redis DSN<br>(if applicable)', function ($args) {
        $name = $args["label_for"];
        $placeholder = $args["placeholder"];
        $value = esc_attr(get_option("crowdsec_redis_dsn"));

        if (false) { // TODO check if it's a valid DSN
            echo "Incorrect ... " . $value . ".\n";
        }
        //MEMCACHED_DSN: memcached://localhost:11211
        echo '<input type="string" class="regular-text" name="' . $name . '"' .
            ' value="' . $value . '" placeholder="' . $placeholder . '">' .
            '<p>Fill in this field only if you have chosen the Redis cache.<br>Example of DSN: redis://localhost:6379.';
    }, 'crowdsec_advanced_settings', 'crowdsec_admin_advanced', array(
        'label_for' => 'crowdsec_redis_dsn',
        'placeholder' => 'redis://...',
    ));
    register_setting('crowdsec_plugin_advanced_settings', 'crowdsec_redis_dsn', function ($input) {
        $input = esc_attr($input);
        if (false) { // P2 check if its it's a valid DSN
            $crowdsec_activated = esc_attr(get_option("crowdsec_redis_dsn"));
            if ($crowdsec_activated) {
                add_settings_error("Redis DSN", "crowdsec_error", "error message...");
                return $input;
            }
        }
        return $input;
    });

    // Field "crowdsec_memcached_dsn"
    add_settings_field('crowdsec_memcached_dsn', 'Memcached DSN<br>(if applicable)', function ($args) {
        $name = $args["label_for"];
        $placeholder = $args["placeholder"];
        $value = esc_attr(get_option("crowdsec_memcached_dsn"));

        if (false) { // TODO check if it's a valid DSN
            echo "Incorrect ... " . $value . ".\n";
        }
        echo '<input type="string" class="regular-text" name="' . $name . '"' .
            ' value="' . $value . '"placeholder="' . $placeholder . '">' .
            '<p>Fill in this field only if you have chosen the Memcached cache.<br>Example of DSN: memcached://localhost:11211.';
    }, 'crowdsec_advanced_settings', 'crowdsec_admin_advanced', array(
        'label_for' => 'crowdsec_memcached_dsn',
        'placeholder' => 'memcached://...',
    ));
    register_setting('crowdsec_plugin_advanced_settings', 'crowdsec_memcached_dsn', function ($input) {
        $input = esc_attr($input);
        if (false) { // P2 check if its it's a valid DSN
            $crowdsec_activated = esc_attr(get_option("crowdsec_memcached_dsn"));
            if ($crowdsec_activated) {
                add_settings_error("Memcached DSN", "crowdsec_error", "error message...");
                return $input;
            }
        }
        return $input;
    });

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
        }, 'crowdsec_advanced_settings', 'crowdsec_admin_advanced', array(
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
    add_settings_field('crowdsec_clean_ip_cache_duration', 'Recheck clean IPs each', function ($args) {
        $name = $args["label_for"];
        $placeholder = $args["placeholder"];
        $value = esc_attr(get_option("crowdsec_clean_ip_cache_duration"));
        echo '<input style="width: 115px;" type="number" class="regular-text"' .
            'name="' . $name . '" value="' . $value . '" placeholder="' . $placeholder . '">' .
            '<p>The duration (in seconds) between re-asking LAPI about an already checked IP.<br>Our advice is between 1 and 60 seconds.';
    }, 'crowdsec_advanced_settings', 'crowdsec_admin_advanced', array(
        'label_for' => 'crowdsec_clean_ip_cache_duration',
        'placeholder' => '...',
    ));
    register_setting('crowdsec_plugin_advanced_settings', 'crowdsec_clean_ip_cache_duration', function ($input) {
        $input = (int)esc_attr($input);
        if ($input <= 0) {
            add_settings_error("Recheck clean IPs each", "crowdsec_error", "Recheck clean IPs each: Minimum is 1 second.");
            return "1";
        }
        return (string)$input;
    });

    // Field "crowdsec_fallback_remediation"
    add_settings_field('crowdsec_fallback_remediation', 'Fallback remediation', function ($args) {
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
    }, 'crowdsec_advanced_settings', 'crowdsec_admin_advanced', array(
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
