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

function getCrowdSecLoggerInstance(): Logger
{
    // Singleton for this function

    global $crowdSecLogger;
    if ($crowdSecLogger) {
        return $crowdSecLogger;
    }

    // Log more data if WP_DEBUG=1

    $logger = new Logger('wp_bouncer');

    $fileHandler = new RotatingFileHandler(CROWDSEC_LOG_PATH, 0, Logger::INFO);
    $fileHandler->setFormatter(new LineFormatter("%datetime%|%level%|%context%\n"));
    $logger->pushHandler($fileHandler);

    // Set custom readable logger for WP_DEBUG=1
    if (WP_DEBUG) {
        $debugFileHandler = new RotatingFileHandler(CROWDSEC_DEBUG_LOG_PATH, 0, Logger::DEBUG);
        if (class_exists('\Bramus\Monolog\Formatter\ColoredLineFormatter')) {
            $debugFileHandler->setFormatter(new \Bramus\Monolog\Formatter\ColoredLineFormatter(null, "[%datetime%] %message% %context%\n", 'H:i:s'));
            $logger->pushHandler($debugFileHandler);
        }
    }

    return $logger;
}

/** @var AbstractAdapter|null */
$crowdSecCacheAdapterInstance = null;

function getCacheAdapterInstance(string $forcedCacheSystem = null): AbstractAdapter
{
    // Singleton for this function

    global $crowdSecCacheAdapterInstance;
    if (!$forcedCacheSystem && $crowdSecCacheAdapterInstance) {
        return $crowdSecCacheAdapterInstance;
    }

    $cacheSystem = $forcedCacheSystem ?: esc_attr(get_option('crowdsec_cache_system'));
    switch ($cacheSystem) {
        case CROWDSEC_CACHE_SYSTEM_PHPFS:
            $crowdSecCacheAdapterInstance = new PhpFilesAdapter('', 0, CROWDSEC_CACHE_PATH);
            break;

        case CROWDSEC_CACHE_SYSTEM_MEMCACHED:
            $memcachedDsn = esc_attr(get_option('crowdsec_memcached_dsn'));
            if (empty($memcachedDsn)) {
                throw new WordpressCrowdSecBouncerException('The selected cache technology is Memcached.'.
                ' Please set a Memcached DSN or select another cache technology.');
            }

            $crowdSecCacheAdapterInstance = new MemcachedAdapter(MemcachedAdapter::createConnection($memcachedDsn));
            break;

        case CROWDSEC_CACHE_SYSTEM_REDIS:
            $redisDsn = esc_attr(get_option('crowdsec_redis_dsn'));
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
            throw new WordpressCrowdSecBouncerException('Unknow selected cache technology.');
    }

    return $crowdSecCacheAdapterInstance;
}

$crowdSecBouncer = null;

function getBouncerInstance(string $forcedCacheSystem = null): Bouncer
{
    // Singleton for this function

    global $crowdSecBouncer;
    if (!$forcedCacheSystem && $crowdSecBouncer) {
        return $crowdSecBouncer;
    }

    // Parse Wordpress Options.

    $apiUrl = esc_attr(get_option('crowdsec_api_url'));
    if (empty($apiUrl)) {
        throw new WordpressCrowdSecBouncerException('Bouncer enabled but no API URL provided');
    }
    $apiKey = esc_attr(get_option('crowdsec_api_key'));
    if (empty($apiKey)) {
        throw new WordpressCrowdSecBouncerException('Bouncer enabled but no API key provided');
    }
    $isStreamMode = !empty(get_option('crowdsec_stream_mode'));
    $cleanIpCacheDuration = (int) get_option('crowdsec_clean_ip_cache_duration');
    $badIpCacheDuration = (int) get_option('crowdsec_bad_ip_cache_duration');
    $fallbackRemediation = esc_attr(get_option('crowdsec_fallback_remediation'));
    $bouncingLevel = esc_attr(get_option('crowdsec_bouncing_level'));

    // Init Bouncer instance

    switch ($bouncingLevel) {
        case CROWDSEC_BOUNCING_LEVEL_DISABLED:
            $maxRemediationLevel = Constants::REMEDIATION_BYPASS;
            break;
        case CROWDSEC_BOUNCING_LEVEL_FLEX:
            $maxRemediationLevel = Constants::REMEDIATION_CAPTCHA;
            break;
        case CROWDSEC_BOUNCING_LEVEL_NORMAL:
            $maxRemediationLevel = Constants::REMEDIATION_BAN;
            break;
        case CROWDSEC_BOUNCING_LEVEL_PARANOID:
            $maxRemediationLevel = Constants::REMEDIATION_BAN;
            break;
        default:
            throw new Exception("Unknown $bouncingLevel");
    }

    $logger = getCrowdSecLoggerInstance();

    // Instanciate the bouncer
    try {
        $cacheAdapter = getCacheAdapterInstance($forcedCacheSystem);
    } catch (Symfony\Component\Cache\Exception\InvalidArgumentException $e) {
        throw new WordpressCrowdSecBouncerException($e->getMessage());
    }

    try {
        $bouncer = new Bouncer($cacheAdapter, $logger);
        $bouncer->configure([
        'api_key' => $apiKey,
        'api_url' => $apiUrl,
        'api_user_agent' => CROWDSEC_BOUNCER_USER_AGENT,
        'live_mode' => !$isStreamMode,
        'max_remediation_level' => $maxRemediationLevel,
        'fallback_remediation' => $fallbackRemediation,
        'cache_expiration_for_clean_ip' => $cleanIpCacheDuration,
        'cache_expiration_for_bad_ip' => $badIpCacheDuration,
    ], $cacheAdapter);
    } catch (BouncerException $e) {
        throw new WordpressCrowdSecBouncerException($e->getMessage());
    }

    return $bouncer;
}
