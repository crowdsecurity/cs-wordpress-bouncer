<?php

define('CROWDSEC_STANDALONE_RUNNING_CONTEXT', true);

require_once __DIR__.'/../vendor/autoload.php';

require_once __DIR__.'/Bouncer.php';

require_once __DIR__.'/standalone-settings.php';

use CrowdSecBouncer\BouncerException;


// If there is any technical problem while bouncing, don't block the user.
try {
    /** @var array $crowdSecJsonStandaloneConfig */
    $crowdSecConfigs = json_decode($crowdSecJsonStandaloneConfig, true);
    $bouncer = new Bouncer($crowdSecConfigs);
    $bouncer->run();
} catch (\Throwable $e) {
    // Try to log in the debug.log file of WordPress if bouncer logger is not ready
    if (!isset($bouncer) || !$bouncer->getLogger()) {
        error_log(print_r('safelyBounce error:' . $e->getMessage() .
                          ' in file:' . $e->getFile() .
                          '(line ' . $e->getLine() . ')', true
        ));
    }
    $displayErrors =  (bool)($crowdSecConfigs['crowdsec_display_errors'] ?? false);
    if (true === $displayErrors) {
        throw new BouncerException($e->getMessage(), $e->getCode(), $e);
    }
}


define("ALREADY_BOUNCED_WITH_STANDALONE", true);
