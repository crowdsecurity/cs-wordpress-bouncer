<?php

define('CROWDSEC_STANDALONE_RUNNING_CONTEXT', true);

require_once __DIR__.'/bounce-current-ip.php';

use CrowdSecWordPressBouncer\Constants;
use CrowdSecWordPressBouncer\Bouncer;
use CrowdSecBouncer\BouncerException;
use Psr\Cache\CacheException;

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
        $logger = $bouncer->getLogger();
        $logger->debug('Running in auto_prepend_file mode');
        $bouncer->run();
    } else {
        throw new BouncerException('No setting file found for the auto_prepend_file mode.');
    }
} catch (CacheException|\Throwable $e) {
    handleException($e, $bouncer ?? null);
}


define("ALREADY_BOUNCED_WITH_STANDALONE", true);
