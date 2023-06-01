<div class="wrap">
	<h1>Configure your CrowdSec Bouncer</h1>
    <script type="text/javascript">

        jQuery(() => {
            const $authType = jQuery('[name=crowdsec_auth_type]');
            const $verifyPeer = jQuery('[name=crowdsec_tls_verify_peer]');
            const $apiKeyTr = jQuery('[name=crowdsec_api_key]').parent().parent();
            const $tlsCertTr = jQuery('[name=crowdsec_tls_cert_path]').parent().parent();
            const $tlsKeyTr = jQuery('[name=crowdsec_tls_key_path]').parent().parent();
            const $tlsVerifyPeerTr = $verifyPeer.parent().parent().parent();
            const $tlsCaTr = jQuery('[name=crowdsec_tls_ca_cert_path]').parent().parent();

            function handleCaCert () {
                if(!$verifyPeer.is(":checked")) {
                    $tlsCaTr.hide();
                } else {$tlsCaTr.show()}
            }

            function updateTlsDisplay () {
                $tlsCertTr.addClass('crowdsec-tls');
                $tlsKeyTr.addClass('crowdsec-tls');
                $tlsVerifyPeerTr.removeClass('ui-toggle').addClass('crowdsec-tls');
                $tlsCaTr.addClass('crowdsec-tls');
                $apiKeyTr.addClass('crowdsec-api-key');
                switch ($authType.val()) {
                    case 'api_key':
                        jQuery('[class=crowdsec-tls]').hide();
                        jQuery('[class=crowdsec-api-key]').show();
                        break;
                    case 'tls':
                        jQuery('[class=crowdsec-tls]').show();
                        jQuery('[class=crowdsec-api-key]').hide();
                        if(!$verifyPeer.is(":checked")) {
                            $tlsCaTr.hide();
                        }
                        break;
                    default:
                        jQuery('[class=crowdsec-tls]').hide();
                        jQuery('[class=crowdsec-api-key]').show();
                }
            }
            updateTlsDisplay();
            $authType.change(updateTlsDisplay);
            $verifyPeer.change(handleCaCert);
        });
    </script>
	<?php settings_errors(); ?>
	<div class="tab-content">
		<div id="tab-1" class="tab-pane active">
			<form method="post" action="<?php echo (is_multisite()) ? add_query_arg( 'action', 'crowdsec_settings', 'edit.php' ) : 'options.php';?>">
				<?php
                if(is_multisite()){
                    echo '<input type="hidden" name="action" value="crowdsec_settings_update"/>';
                    echo '<input type="hidden" name="nonce" value="'.wp_create_nonce('crowdsec-settings-update').'"/>';
                }else{
                    settings_fields('crowdsec_plugin_settings');
                }
                do_settings_sections('crowdsec_settings');
                ?>
				<?php
                submit_button();
                ?>
			</form>
            <h2><?php echo __("Test your settings");?></h2>
            <p><?php echo __("Here you can check if your <b>saved</b> settings are correct.");?></p>
            <p><?php echo __("Click the 'Test bouncing' button and the bouncer will try to get the remediation for the following IP:");?></p>
            <form action="<?php echo admin_url('admin-post.php') ?>" method="post" id="crowdsec_action_test_connection">
                <input type="hidden" name="action" value="crowdsec_test_connection"/>
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('crowdsec_test_connection'); ?>"/>
                <input type="text" name="crowdsec_test_connection_ip" value="<?php echo $_SERVER['REMOTE_ADDR'];?>"/>
                <?php
                submit_button('Test bouncing', 'secondary');
                ?>
            </form>
            <p><?php echo __("Click the 'Test geolocation' button to try getting country for the following IP:");?></p>
            <form action="<?php echo admin_url('admin-post.php') ?>" method="post" id="crowdsec_action_test_geolocation">
                <input type="hidden" name="action" value="crowdsec_test_geolocation"/>
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('crowdsec_test_geolocation'); ?>"/>
                <input type="text" name="crowdsec_test_geolocation_ip" value="<?php echo $_SERVER['REMOTE_ADDR'];?>"/>
                <?php
                submit_button('Test geolocation', 'secondary');
                ?>
            </form>
			<p>
			Feel free to ask any questions about this plugin, make your suggestions or raise issues on the <a href="https://wordpress.org/support/plugin/crowdsec/">plugin support page</a> or directly on <a href="https://github.com/crowdsecurity/cs-wordpress-bouncer/issues/new">Github</a>.
			</p>
		</div>
	</div>
</div>
