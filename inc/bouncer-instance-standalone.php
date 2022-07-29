<?php
require_once __DIR__ . '/Constants.php';
use CrowdSecBouncer\Bouncer;
use CrowdSecBouncer\BouncerException;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;


/** @var Logger|null */
$crowdSecLogger = null;

function getStandaloneCrowdSecLoggerInstance(string $crowdsecLogPath, bool $debugMode, string $crowdsecDebugLogPath): Logger
{
    // Singleton for this function
    global $crowdSecLogger;
    if ($crowdSecLogger) {
        return $crowdSecLogger;
    }

    // Log more data if debug mode is enabled

    $logger = new Logger('wp_bouncer');

    $fileHandler = new RotatingFileHandler($crowdsecLogPath, 0, Logger::INFO);
    $fileHandler->setFormatter(new LineFormatter("%datetime%|%level%|%context%\n"));
    $logger->pushHandler($fileHandler);

    // Set custom readable logger for debugMode=1
    if ($debugMode) {
        $debugFileHandler = new RotatingFileHandler($crowdsecDebugLogPath, 0, Logger::DEBUG);
		$debugFileHandler->setFormatter(new LineFormatter("%datetime%|%level%|%context%\n"));
		$logger->pushHandler($debugFileHandler);
    }

    return $logger;
}

$crowdSecBouncer = null;

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
    $logger = getStandaloneCrowdSecLoggerInstance(Constants::CROWDSEC_LOG_PATH, $isDebug,
        Constants::CROWDSEC_DEBUG_LOG_PATH);

    try {
        $finalConfigs = array_merge($configs, ['max_remediation_level' => $maxRemediationLevel]);
        $bouncer = new Bouncer($finalConfigs, $logger);

    } catch (Exception $e) {
        throw new BouncerException($e->getMessage());
    }
    return $bouncer;
}
