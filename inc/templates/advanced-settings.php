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
		<form action="<?php echo admin_url('admin-post.php'); ?>" method="post" onsubmit="return confirm('Are you sure you want to completely clear the cache?')">
			<input type="hidden" name="action" value="refresh_cache">
			<input type="submit" value="Clear the cache" class="button button-primary">
		</form>
		</div>
	</div>
</div>