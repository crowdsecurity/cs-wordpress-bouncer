<div class="wrap">
	<h1>Configure your CrowdSec Bouncer</h1>
	<?php settings_errors(); ?>
	<div class="tab-content">
		<div id="tab-1" class="tab-pane active">
		<form method="post" action="options.php">
				<?php 
					settings_fields( 'crowdsec_plugin_settings' );
					do_settings_sections( 'crowdsec_settings' );
				?>
				<?php
					submit_button();
				?>
		</form>
		
		<br/>
		<form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
			<input type="hidden" name="action" value="refresh_cache">
			<input type="submit" value="Refresh Cache" class="button button-primary">
		</form>
		</div>
	</div>
</div>