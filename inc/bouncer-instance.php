<?php

use CrowdSecBouncer\Bouncer;
use CrowdSecBouncer\Constants;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

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

    $loggerLevel = WP_DEBUG ? Logger::DEBUG : Logger::INFO;
    $logger = new Logger('wp_bouncer');
    $fileHandler = new RotatingFileHandler(__DIR__.'/../logs/crowdsec.log', 0, $loggerLevel);

    // Set custom readble logger for WP_DEBUG=1
    if (WP_DEBUG) {
        $fileHandler->setFormatter(new \Bramus\Monolog\Formatter\ColoredLineFormatter(null, "[%datetime%] %message% %context%\n", 'H:i:s'));
    }
    $logger->pushHandler($fileHandler);

    return $logger;
}

function getCacheAdapterInstance(string $forcedCacheSystem = null): AbstractAdapter
{
    $cacheSystem = $forcedCacheSystem ?: esc_attr(get_option('crowdsec_cache_system'));
    switch ($cacheSystem) {
        case CROWDSEC_CACHE_SYSTEM_PHPFS:
            return new PhpFilesAdapter('', 0, __DIR__.'/.cache');

        case CROWDSEC_CACHE_SYSTEM_MEMCACHED:
            $memcachedDsn = esc_attr(get_option('crowdsec_memcached_dsn'));
            if (empty($memcachedDsn)) {
                throw new WordpressCrowdSecBouncerException('Memcached selected but no DSN provided.');
            }

            return new MemcachedAdapter(MemcachedAdapter::createConnection($memcachedDsn));

        case CROWDSEC_CACHE_SYSTEM_REDIS:
            $redisDsn = esc_attr(get_option('crowdsec_redis_dsn'));
            if (empty($redisDsn)) {
                throw new WordpressCrowdSecBouncerException('Redis selected but no DSN provided.');
            }

            return new RedisAdapter(RedisAdapter::createConnection($redisDsn));
    }
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
    $cacheAdapter = getCacheAdapterInstance($forcedCacheSystem);
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

    return $bouncer;
}
