<div class="wrap">
	<h1>CrowdSec Advanced Settings</h1>
	<?php settings_errors(); ?>
	<div class="tab-content">
		<div id="tab-1" class="tab-pane active">
		<form method="post" action="options.php">
				<?php 
					settings_fields( 'crowdsec_plugin_advanced_settings' );
					do_settings_sections( 'crowdsec_advanced_settings' );
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