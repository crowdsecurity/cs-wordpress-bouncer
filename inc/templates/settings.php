<div class="wrap">
	<h1>Configure your CrowdSec Bouncer</h1>
	<?php settings_errors(); ?>
	<div class="tab-content">
		<div id="tab-1" class="tab-pane active">
			<form method="post" action="options.php">
				<?php
				settings_fields('crowdsec_plugin_settings');
				do_settings_sections('crowdsec_settings');
				?>
				<?php
				submit_button();
				?>
			</form>
		</div>
	</div>
</div>