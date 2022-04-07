<?php

use CrowdSecBouncer\Constants;

require_once __DIR__ . '/Bounce.php';
require_once __DIR__.'/options-config.php';

function getConfigs()
{
	$crowdSecWpPluginOptions = getCrowdSecOptionsConfig();
	$finalConfigs = [];
	foreach ($crowdSecWpPluginOptions as $option) {
		$finalConfigs[$option['name']] = get_option($option['name']);
	}

	return $finalConfigs;
}


function safelyBounceCurrentIp()
{
	if(defined("ALREADY_BOUNCED_WITH_STANDALONE")){
		return;
	}
	$crowdSecConfig = getConfigs();
    $crowdSecBounce = new Bounce();
    $crowdSecBounce->safelyBounce($crowdSecConfig);
}
