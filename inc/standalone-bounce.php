<?php

define('CROWDSEC_STANDALONE_RUNNING_CONTEXT', true);

require_once __DIR__.'/bounce-current-ip.php';

use CrowdSecWordPressBouncer\Constants;
use CrowdSecWordPressBouncer\Bouncer;
use CrowdSecBouncer\BouncerException;
use Psr\Cache\CacheException;

define("ALREADY_BOUNCED_WITH_STANDALONE", true);
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
        $logger->debug('Running in auto_prepend_file mode',
            [
                'type' => 'AUTO_PREPEND_FILE_MODE',
                'message' => 'Server is configured to auto_prepend this file '. __FILE__
            ]
        );
        if(empty($crowdSecConfigs['crowdsec_auto_prepend_file_mode'])){
            $logger->warning('Will not bounce because the auto_prepend_file mode is not enabled',
                [
                    'type' => 'AUTO_PREPEND_FILE_MODE',
                    'message' => 'Please enable the auto_prepend_file mode in the settings to use the bouncer. Or remove the auto_prepend_file directive from your server configuration.'
                ]
            );
            return;
        }
        $bouncer->run();
    } else {
        throw new BouncerException('No setting file found for the auto_prepend_file mode.');
    }
} catch (CacheException|\Throwable $e) {
    handleException($e, $bouncer ?? null);
}



