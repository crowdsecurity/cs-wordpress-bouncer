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
            <h2><?php echo __("Test your settings");?></h2>
            <p><?php echo __("Here you can check if your saved settings are correct.");?></p>
            <p><?php echo __("Click the 'Test bouncing' button and the bouncer will try to get the remediation for the following IP:");?></p>
            <form action="admin-post.php" method="post" id="crowdsec_action_test_connection">
                <input type="hidden" name="action" value="crowdsec_test_connection"/>
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('crowdsec_test_connection'); ?>"/>
                <input type="text" name="crowdsec_test_connection_ip" value="<?php echo $_SERVER['REMOTE_ADDR'];?>"/>
                <?php
                submit_button('Test bouncing', 'secondary');
                ?>
            </form>
			<p>
			Feel free to ask any questions about this plugin, make your suggestions or raise issues on the <a href="https://wordpress.org/support/plugin/crowdsec/">plugin support page</a> or directly on <a href="https://github.com/crowdsecurity/cs-wordpress-bouncer/issues/new">Github</a>.
			</p>
		</div>
	</div>
</div>