<?php

use CrowdSecBouncer\Bouncer;
use CrowdSecBouncer\BouncerException;
use CrowdSecBouncer\Constants;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

/** @var Logger|null */
$crowdSecLogger = null;

function getStandAloneCrowdSecLoggerInstance(string $crowdsecLogPath, bool $debugMode, string $crowdsecDebugLogPath): Logger
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

/** @var AbstractAdapter|null */
$crowdSecCacheAdapterInstance = null;

function getCacheAdapterInstanceStandAlone(string $cacheSystem, string $memcachedDsn, string $redisDsn, string $fsCachePath, string $forcedCacheSystem = null): AbstractAdapter
{
    // Singleton for this function

    global $crowdSecCacheAdapterInstance;
    if (!$forcedCacheSystem && $crowdSecCacheAdapterInstance) {
        return $crowdSecCacheAdapterInstance;
    }

    $cacheSystem = $forcedCacheSystem ?: $cacheSystem;
    switch ($cacheSystem) {
        case Constants::CACHE_SYSTEM_PHPFS:
            $crowdSecCacheAdapterInstance = new PhpFilesAdapter('', 0, $fsCachePath);
            break;

        case Constants::CACHE_SYSTEM_MEMCACHED:
            if (empty($memcachedDsn)) {
                throw new WordpressCrowdSecBouncerException('The selected cache technology is Memcached.'.
                ' Please set a Memcached DSN or select another cache technology.');
            }

            $crowdSecCacheAdapterInstance = new MemcachedAdapter(MemcachedAdapter::createConnection($memcachedDsn));
            break;

        case Constants::CACHE_SYSTEM_REDIS:
            if (empty($redisDsn)) {
                throw new WordpressCrowdSecBouncerException('The selected cache technology is Redis.'.
                ' Please set a Redis DSN or select another cache technology.');
            }

            try {
                $crowdSecCacheAdapterInstance = new RedisAdapter(RedisAdapter::createConnection($redisDsn));
            } catch (InvalidArgumentException $e) {
                throw new WordpressCrowdSecBouncerException('Error when connecting to Redis.'.
                ' Please fix the Redis DSN or select another cache technology.');
            }

            break;
        default:
            throw new WordpressCrowdSecBouncerException('Unknown selected cache technology.');
    }

    return $crowdSecCacheAdapterInstance;
}

$crowdSecBouncer = null;

function getBouncerInstanceStandAlone(string $apiUrl, string $apiKey, bool $isStreamMode, int $cleanIpCacheDuration, int $badIpCacheDuration, string $fallbackRemediation, string $bouncingLevel, string $crowdSecBouncerUserAgent, Logger $logger, string $cacheSystem, string $memcachedDsn, string $redisDsn, string $fsCachePath, string $forcedCacheSystem = null): Bouncer
{
    // Singleton for this function

    global $crowdSecBouncer;
    if (!$forcedCacheSystem && $crowdSecBouncer) {
        return $crowdSecBouncer;
    }

    // Parse WordPress Options.
    if (empty($apiUrl)) {
        throw new WordpressCrowdSecBouncerException('Bouncer enabled but no API URL provided');
    }

    if (empty($apiKey)) {
        throw new WordpressCrowdSecBouncerException('Bouncer enabled but no API key provided');
    }

    // Init Bouncer instance

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
            throw new Exception("Unknown $bouncingLevel");
    }

    // Instantiate the bouncer
    try {
        $cacheAdapter = getCacheAdapterInstanceStandAlone($cacheSystem, $memcachedDsn, $redisDsn, $fsCachePath, $forcedCacheSystem);
    } catch (Symfony\Component\Cache\Exception\InvalidArgumentException $e) {
        throw new WordpressCrowdSecBouncerException($e->getMessage());
    }

    try {
        $bouncer = new Bouncer($cacheAdapter, $logger);
        $bouncer->configure([
        'api_key' => $apiKey,
        'api_url' => $apiUrl,
        'api_user_agent' => $crowdSecBouncerUserAgent,
        'live_mode' => !$isStreamMode,
        'max_remediation_level' => $maxRemediationLevel,
        'fallback_remediation' => $fallbackRemediation,
        'cache_expiration_for_clean_ip' => $cleanIpCacheDuration,
        'cache_expiration_for_bad_ip' => $badIpCacheDuration,
    ]);
    } catch (BouncerException $e) {
        throw new WordpressCrowdSecBouncerException($e->getMessage());
    }

    return $bouncer;
}
