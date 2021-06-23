<?php

require_once __DIR__.'/Bounce.php';

require_once __DIR__.'/standalone-settings.php';

function safelyBounceCurrentIp()
{
    global $crowdSecJsonStandaloneConfig;
    $crowdSecConfig = json_decode($crowdSecJsonStandaloneConfig, true);
    if (!count($crowdSecConfig)) {
        return;
    }
    $crowdSecBounce = new Bounce();
    if ($crowdSecBounce->init($crowdSecConfig)) {
        $crowdSecBounce->safelyBounce();
    }
}
