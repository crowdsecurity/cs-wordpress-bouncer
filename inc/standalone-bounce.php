<?php

define('CROWDSEC_STANDALONE_RUNNING_CONTEXT', true);

require_once __DIR__.'/../vendor/autoload.php';

require_once __DIR__.'/Bounce.php';

require_once __DIR__.'/standalone-settings.php';
require_once __DIR__.'/bouncer-instance-standalone.php';

$crowdSecConfig = json_decode($crowdSecJsonStandaloneConfig, true);
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
