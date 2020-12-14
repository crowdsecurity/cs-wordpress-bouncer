<?php

use CrowdSecBouncer\Bouncer;
use CrowdSecBouncer\Constants;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;


function getCacheAdapterInstance(): AbstractAdapter
{
    switch (esc_attr(get_option('crowdsec_cache_system'))) {

        case CROWDSEC_CACHE_SYSTEM_PHPFS:
            return new PhpFilesAdapter('', 0, __DIR__ . '/.cache');

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

$bouncer = null;

function getBouncerInstance(): Bouncer
{
    // Singleton for this function
    global $bouncer;
    if ($bouncer) {
        return $bouncer;
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
    $cleanIpCacheDuration = (int)get_option('crowdsec_clean_ip_cache_duration');
    $fallbackRemediation = esc_attr(get_option('crowdsec_fallback_remediation'));
    $bouncingLevel = esc_attr(get_option("crowdsec_bouncing_level"));

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
            // TODO P2 add "minimum remediation" feature in lib + set it to ban in this case
            break;
        default:
            throw new Exception("Unknown $bouncingLevel");
    }

    // Display Library log in debug mode
    $logger = null;
    if (WP_DEBUG) {
        $logger = new Logger('wordpress');
        $fileHandler = new RotatingFileHandler(__DIR__ . '/crowdsec.log', 0, Logger::DEBUG);
        $logger->pushHandler($fileHandler);
    }

    // Instanciate the bouncer
    $bouncer = new Bouncer($logger);
    $cacheAdapter = getCacheAdapterInstance();
    $bouncer->configure([
        'api_key' => $apiKey,
        'api_url' => $apiUrl,
        'api_user_agent' => CROWDSEC_BOUNCER_USER_AGENT,
        //'api_timeout' => null // TODO P3 make a advanced settings
        'live_mode' => !$isStreamMode,
        'max_remediation_level' => $maxRemediationLevel,
        'fallback_remediation' => $fallbackRemediation,
        'cache_expiration_for_clean_ip' => $cleanIpCacheDuration
    ], $cacheAdapter);
    return $bouncer;
}
