<div class="wrap">
	<h1>CrowdSec Advanced Settings</h1>
	<script type="text/javascript">

	jQuery(() => {
        // Cache
		const $cacheTechno = jQuery('[name=crowdsec_cache_system]');
		const $cacheTechnoDiv = $cacheTechno.parent().parent();
        const $redisDsn = jQuery('[name=crowdsec_redis_dsn]');
		const $redisDsnDiv = $redisDsn.parent().parent();
        const $memcachedDsn = jQuery('[name=crowdsec_memcached_dsn]');
		const $memcachedDsnDiv = $memcachedDsn.parent().parent();
		$cacheTechnoDiv.insertBefore($redisDsnDiv);
        // AppSec
        const $useAppSec = jQuery('[name=crowdsec_use_appsec]');
        const $appSecUrl = jQuery('[name=crowdsec_appsec_url]');
        const $appSecUrlTr = $appSecUrl.parent().parent();
        const $appSecTimeoutTr = jQuery('[name=crowdsec_appsec_timeout_ms]').parent().parent();
        const $appSecFallback = jQuery('[name=crowdsec_appsec_fallback_remediation]').parent().parent();
        const $appSecMaxBodyAction = jQuery('[name=crowdsec_appsec_body_size_exceeded_action]').parent().parent();
        const $appSecMaxBodySizeTr = jQuery('[name=crowdsec_appsec_max_body_size_kb]').parent().parent();
        // Geolocation
        const $geolocationEnabled = jQuery('[name=crowdsec_geolocation_enabled]');
        const $geolocationTypeTr = jQuery('[name=crowdsec_geolocation_type]').parent().parent();
        const $geolocationMaxmindDatabaseTypeTr = jQuery('[name=crowdsec_geolocation_maxmind_database_type]').parent().parent();
        const $geolocationMaxmindDatabasePath = jQuery('[name=crowdsec_geolocation_maxmind_database_path]');
        const $geolocationMaxmindDatabasePathTr = $geolocationMaxmindDatabasePath.parent().parent();
        const $geolocationCacheDurationTr = jQuery('[name=crowdsec_geolocation_cache_duration]').parent().parent();
        // Stream Mode
        const $streamMode = jQuery('[name=crowdsec_stream_mode]');
        const $streamModeRefreshFrequency = jQuery('[name=crowdsec_stream_mode_refresh_frequency]');
        const $streamModeRefreshFrequencyTr = $streamModeRefreshFrequency.parent().parent();

        function updateStreamModeDisplay () {
            if($streamMode.is(":checked")) {
                $streamModeRefreshFrequency.attr('required', 'required');
                $streamModeRefreshFrequencyTr.show("slow");
            } else {
                $streamModeRefreshFrequency.removeAttr('required');
                $streamModeRefreshFrequencyTr.hide();
            }
        }

        function updateGeolocationDisplay () {
            $geolocationTypeTr.addClass('crowdsec-geolocation');
            $geolocationMaxmindDatabaseTypeTr.addClass('crowdsec-geolocation');
            $geolocationMaxmindDatabasePathTr.addClass('crowdsec-geolocation');
            $geolocationCacheDurationTr.addClass('crowdsec-geolocation');
            if($geolocationEnabled.is(":checked")) {
                $geolocationMaxmindDatabasePath.attr('required', 'required');
                jQuery('[class=crowdsec-geolocation]').show("slow");
            } else {
                $geolocationMaxmindDatabasePath.removeAttr('required');
                jQuery('[class=crowdsec-geolocation]').hide();
            }
        }

        function updateAppSecDisplay () {
            if($useAppSec.is(":checked")) {
                $appSecUrl.attr('required', 'required');
                $appSecUrlTr.show("slow");
                $appSecTimeoutTr.show("slow");
                $appSecFallback.show("slow");
                $appSecMaxBodyAction.show("slow");
                $appSecMaxBodySizeTr.show("slow");
            } else {
                $appSecUrl.removeAttr('required');
                $appSecUrlTr.hide();
                $appSecTimeoutTr.hide();
                $appSecFallback.hide();
                $appSecMaxBodyAction.hide();
                $appSecMaxBodySizeTr.hide();
            }
        }

		function updateDsnDisplay () {
			switch ($cacheTechno.val()) {
				case 'redis':
                    $redisDsn.attr('required', 'required');
                    $memcachedDsn.removeAttr('required');
					$redisDsnDiv.show("slow");
					$memcachedDsnDiv.hide();
					break;
				case 'memcached':
                    $redisDsn.removeAttr('required');
                    $memcachedDsn.attr('required', 'required');
					$redisDsnDiv.hide();
					$memcachedDsnDiv.show("slow");
					break;
				default:
                    $redisDsn.removeAttr('required');
                    $memcachedDsn.removeAttr('required');
					$redisDsnDiv.hide();
					$memcachedDsnDiv.hide();
			}
		}
        updateStreamModeDisplay();
		updateDsnDisplay();
        updateAppSecDisplay();
        updateGeolocationDisplay();
        $streamMode.change(updateStreamModeDisplay);
		$cacheTechno.change(updateDsnDisplay);
        $useAppSec.change(updateAppSecDisplay);
        $geolocationEnabled.change(updateGeolocationDisplay);
	});
	</script>
	<?php settings_errors(); ?>
	<div class="tab-content">
		<div id="tab-1" class="tab-pane active">
			<form method="post" action="<?php echo (is_multisite()) ? add_query_arg( 'action', 'crowdsec_advanced_settings',	'edit.php' ) : 'options.php';?>">
				<?php
				if(is_multisite()){
					echo '<input type="hidden" name="action" value="crowdsec_advanced_settings_update"/>';
					echo '<input type="hidden" name="nonce" value="'.wp_create_nonce('crowdsec-advanced-settings-update')
						 .'"/>';
				}else{
					settings_fields('crowdsec_plugin_advanced_settings');
				}
				do_settings_sections('crowdsec_advanced_settings');
				?>
				<?php
				submit_button();
				?>
			</form>
		<form action="<?php echo admin_url('admin-post.php'); ?>" method="post" id="crowdsec_action_clear_cache">
			<input type="hidden" name="action" value="crowdsec_clear_cache">
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('crowdsec_clear_cache'); ?>">
		</form>
		<form action="<?php echo admin_url('admin-post.php'); ?>" method="post" id="crowdsec_action_refresh_cache">
			<input type="hidden" name="action" value="crowdsec_refresh_cache">
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('crowdsec_refresh_cache'); ?>">
		</form>
		<form action="<?php echo admin_url('admin-post.php'); ?>" method="post" id="crowdsec_action_prune_cache">
			<input type="hidden" name="action" value="crowdsec_prune_cache">
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('crowdsec_prune_cache'); ?>">
		</form>
		</div>
		<p style="padding-top:15px">
			Feel free to ask any questions about this plugin, make your suggestions or raise issues on the <a href="https://wordpress.org/support/plugin/crowdsec/">plugin support page</a> or directly on <a href="https://github.com/crowdsecurity/cs-wordpress-bouncer/issues/new">Github</a>.
		</p>
	</div>
</div>
