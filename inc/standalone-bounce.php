<?php

define('CROWDSEC_STANDALONE_RUNNING_CONTEXT', true);

require_once __DIR__.'/../vendor/autoload.php';

require_once __DIR__.'/Bounce.php';

require_once __DIR__.'/standalone-settings.php';
require_once __DIR__.'/bouncer-instance-standalone.php';

/** @var array $crowdSecJsonStandaloneConfig */
$crowdSecConfig = json_decode($crowdSecJsonStandaloneConfig, true);
$crowdSecBounce = new Bounce();
$crowdSecBounce->safelyBounce($crowdSecConfig);
define("ALREADY_BOUNCED_WITH_STANDALONE", true);
