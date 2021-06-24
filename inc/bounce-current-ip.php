<?php

require_once __DIR__.'/Bounce.php';

function safelyBounceCurrentIp()
{
    if (!file_exists(__DIR__.'/standalone-settings.php')) {
        return;
    }
    require_once __DIR__.'/standalone-settings.php';
    $crowdSecConfig = json_decode($crowdSecJsonStandaloneConfig, true);
    if (!count($crowdSecConfig)) {
        return;
    }
    $crowdSecBounce = new Bounce();
    if ($crowdSecBounce->init($crowdSecConfig)) {
        $crowdSecBounce->safelyBounce();
    }
}
