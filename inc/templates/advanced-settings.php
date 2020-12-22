<div class="wrap">
	<h1>CrowdSec Advanced Settings</h1>
	<script type="text/javascript">

	jQuery(() => {
		const $cacheTechno = jQuery('[name=crowdsec_cache_system]');
		const $cacheTechnoDiv = $cacheTechno.parent().parent();
		const $redisDsnDiv = jQuery('[name=crowdsec_redis_dsn]').parent().parent();
		const $memcachedDsnDiv = jQuery('[name=crowdsec_memcached_dsn]').parent().parent();
		$cacheTechnoDiv.insertBefore($redisDsnDiv);

		function updateDsnDisplay () {
			switch ($cacheTechno.val()) {
				case 'redis':
					$redisDsnDiv.show();
					$memcachedDsnDiv.hide();
					break;
				case 'memcached':
					$redisDsnDiv.hide();
					$memcachedDsnDiv.show();
					break;
				default:
					$redisDsnDiv.hide();
					$memcachedDsnDiv.hide();
			}
		}
		updateDsnDisplay();
		$cacheTechno.change(updateDsnDisplay);
	});
	</script>
	<?php settings_errors(); ?>
	<div class="tab-content">
		<div id="tab-1" class="tab-pane active">
		<form method="post" action="options.php">
				<?php
                    settings_fields('crowdsec_plugin_advanced_settings');
                    do_settings_sections('crowdsec_advanced_settings');
                ?>
				<?php
                    submit_button();
                ?>
		</form>
		
		<br/>
		<form action="<?php echo admin_url('admin-post.php'); ?>" method="post" id="crowdsec_ation_clear_cache">
			<input type="hidden" name="action" value="clear_cache">
		</form>
		<form action="<?php echo admin_url('admin-post.php'); ?>" method="post" id="crowdsec_ation_refresh_cache">
			<input type="hidden" name="action" value="refresh_cache">
		</form>
		<form action="<?php echo admin_url('admin-post.php'); ?>" method="post" id="crowdsec_ation_prune_cache">
			<input type="hidden" name="action" value="prune_cache">
		</form>
		</div>
	</div>
</div>