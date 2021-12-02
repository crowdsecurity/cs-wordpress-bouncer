<?php

use CrowdSecBouncer\Constants;

require_once __DIR__ . '/Bounce.php';

function safelyBounceCurrentIp()
{
	if(defined("ALREADY_BOUNCED_WITH_STANDALONE")){
		return;
	}

    if (\PHP_SESSION_NONE === session_status()) {
        session_start();
    }
    if (!file_exists(__DIR__.'/standalone-settings.php')) {
        return;
    }
    require_once __DIR__.'/standalone-settings.php';
    $crowdSecConfig = json_decode($crowdSecJsonStandaloneConfig, true);
    if (!count($crowdSecConfig)) {
        return;
    }
	// Retro compatibility with crowdsec php lib < 0.14.0
    if($crowdSecConfig['crowdsec_bouncing_level'] === 'normal_boucing'){
		$crowdSecConfig['crowdsec_bouncing_level'] = Constants::BOUNCING_LEVEL_NORMAL;
	}elseif($crowdSecConfig['crowdsec_bouncing_level'] === 'flex_boucing'){
		$crowdSecConfig['crowdsec_bouncing_level'] = Constants::BOUNCING_LEVEL_FLEX;
	}

    $crowdSecBounce = new Bounce();
	$crowdSecBounce->setDebug($crowdSecConfig['crowdsec_debug_mode']??false);
	$crowdSecBounce->setDisplayErrors($crowdSecConfig['crowdsec_display_errors'] ?? false);
    if ($crowdSecBounce->init($crowdSecConfig)) {
        $crowdSecBounce->safelyBounce();
    }
    if (\PHP_SESSION_NONE !== session_status()) {
        session_write_close();
    }
}
