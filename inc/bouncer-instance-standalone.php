<?php
require_once __DIR__ . '/Constants.php';
use CrowdSecBouncer\Bouncer;
use CrowdSecBouncer\BouncerException;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

function getStandaloneCrowdSecLoggerInstance(bool $debugMode, bool $disableProd): Logger
{

    $logger = new Logger('wp_bouncer');
    if(!$disableProd){
        $fileHandler = new RotatingFileHandler(Constants::CROWDSEC_LOG_PATH, 0, Logger::INFO);
        $fileHandler->setFormatter(new LineFormatter("%datetime%|%level%|%context%\n"));
        $logger->pushHandler($fileHandler);
    }
    // Log more data if debug mode is enabled
    if ($debugMode) {
        $debugFileHandler = new RotatingFileHandler(Constants::CROWDSEC_DEBUG_LOG_PATH, 0, Logger::DEBUG);
		$debugFileHandler->setFormatter(new LineFormatter("%datetime%|%level%|%context%\n"));
		$logger->pushHandler($debugFileHandler);
    }

    return $logger;
}

function getBouncerInstanceStandalone(array $configs): Bouncer
{
    // Init Bouncer instance
    $bouncingLevel = $configs['bouncing_level'];
    switch ($bouncingLevel) {
        case Constants::BOUNCING_LEVEL_DISABLED:
            $maxRemediationLevel = Constants::REMEDIATION_BYPASS;
            break;
        case Constants::BOUNCING_LEVEL_FLEX:
            $maxRemediationLevel = Constants::REMEDIATION_CAPTCHA;
            break;
        case Constants::BOUNCING_LEVEL_NORMAL:
            $maxRemediationLevel = Constants::REMEDIATION_BAN;
            break;
        default:
            throw new BouncerException("Unknown $bouncingLevel");
    }
    $isDebug = !empty($configs['debug_mode']);
    $disableProd = !empty($configs['disable_prod_log']);
    $logger = getStandaloneCrowdSecLoggerInstance($isDebug, $disableProd);

    try {
        $finalConfigs = array_merge($configs, ['max_remediation_level' => $maxRemediationLevel]);
        $bouncer = new Bouncer($finalConfigs, $logger);

    } catch (Exception $e) {
        throw new BouncerException($e->getMessage());
    }
    return $bouncer;
}
