<?php

define('CROWDSEC_STANDALONE_RUNNING_CONTEXT', true);

require_once __DIR__.'/../vendor/autoload.php';

require_once __DIR__.'/Bouncer.php';
require_once __DIR__.'/Constants.php';


use CrowdSecBouncer\BouncerException;


// If there is any technical problem while bouncing, don't block the user.
try {

    $jsonConfigs = @include_once __DIR__.'/standalone-settings.php';

    if($jsonConfigs){
        $crowdSecConfigs = json_decode($jsonConfigs, true);
        if(isset($crowdSecConfigs['crowdsec_bouncing_level'])){
            if(Constants::BOUNCING_LEVEL_DISABLED === $crowdSecConfigs['crowdsec_bouncing_level']){
                return;
            }
        }
        $bouncer = new Bouncer($crowdSecConfigs);
        $bouncer->run();
    }
} catch (\Throwable $e) {
    // Try to log in the debug.log file of WordPress if bouncer logger is not ready
    if (!isset($bouncer) || !$bouncer->getLogger()) {
        error_log(print_r('safelyBounce error:' . $e->getMessage() .
                          ' in file:' . $e->getFile() .
                          '(line ' . $e->getLine() . ')', true
        ));
        return;
    }
    $displayErrors =  $bouncer->getConfig('display_errors');
    if (true === $displayErrors) {
        throw new BouncerException($e->getMessage(), $e->getCode(), $e);
    }
}


define("ALREADY_BOUNCED_WITH_STANDALONE", true);
