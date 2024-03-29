<?php

define('CROWDSEC_STANDALONE_RUNNING_CONTEXT', true);

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/Bouncer.php';
require_once __DIR__.'/Constants.php';

use CrowdSecBouncer\BouncerException;
use CrowdSecWordPressBouncer\Constants;
use CrowdSecWordPressBouncer\Bouncer;

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
    } else {
        throw new BouncerException('No setting file found for the auto_prepend_file mode.');
    }
} catch (\Throwable $e) {
    // Try to log error if bouncer logger is not ready
    if (!isset($bouncer) || !$bouncer->getLogger()) {
        error_log(print_r('[CrowdSec] safelyBounce error:' . $e->getMessage() .
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
