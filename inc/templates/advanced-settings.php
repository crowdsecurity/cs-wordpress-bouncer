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
