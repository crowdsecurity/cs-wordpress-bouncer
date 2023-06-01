<div class="wrap">
	<h1>Configure your CrowdSec Bouncer</h1>
	<?php settings_errors(); ?>
	<div class="tab-content">
		<div id="tab-1" class="tab-pane active">
			<form method="post" action="<?php echo (is_multisite()) ? add_query_arg( 'action', 'crowdsec_theme_settings',	'edit.php' ) : 'options.php';?>">
				<?php
				if(is_multisite()){
					echo '<input type="hidden" name="action" value="crowdsec_theme_settings_update"/>';
					echo '<input type="hidden" name="nonce" value="'.wp_create_nonce('crowdsec-theme-settings-update')
						 .'"/>';
				}else{
					settings_fields('crowdsec_plugin_theme_settings');
				}
				do_settings_sections('crowdsec_theme_settings');
				?>
				<?php
				submit_button();
				?>
			</form>
			<p>
			Feel free to ask any questions about this plugin, make your suggestions or raise issues on the <a href="https://wordpress.org/support/plugin/crowdsec/">plugin support page</a> or directly on <a href="https://github.com/crowdsecurity/cs-wordpress-bouncer/issues/new">Github</a>.
			</p>
		</div>
	</div>
</div>
