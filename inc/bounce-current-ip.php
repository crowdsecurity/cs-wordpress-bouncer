<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/options-config.php';

use CrowdSecWordPressBouncer\Constants;
use CrowdSecWordPressBouncer\Bouncer;
use CrowdSecBouncer\BouncerException;
use Psr\Cache\CacheException;

function safelyBounceCurrentIp()
{
    if (defined("ALREADY_BOUNCED_WITH_STANDALONE")) {
        return;
    }
    // If there is any technical problem while bouncing, don't block the user.
    try {
        $crowdSecConfigs = getDatabaseConfigs();
        if(isset($crowdSecConfigs['crowdsec_bouncing_level'])){
            if(Constants::BOUNCING_LEVEL_DISABLED === $crowdSecConfigs['crowdsec_bouncing_level']){
                return;
            }
        }
        $bouncer = new Bouncer($crowdSecConfigs);
        $bouncer->run();
    } catch (CacheException|\Throwable $e) {
        handleException($e, $bouncer ?? null);
    }
}

function handleException($e, ?Bouncer $bouncer = null)
{
    // Try to log in the debug.log file of WordPress if bouncer logger is not ready
    if (!$bouncer || !$bouncer->getLogger()) {
        error_log(print_r('[CrowdSec Plugin] safelyBounce error: ' . $e->getMessage() .
                          ' in file: ' . $e->getFile() .
                          '(line ' . $e->getLine() . ')', true
        ));
        return;
    }
    $displayErrors = $bouncer->getConfig('display_errors');
    if (true === $displayErrors) {
        throw new BouncerException($e->getMessage(), $e->getCode(), $e);
    }
}
