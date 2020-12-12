<?php

use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use CrowdSecBouncer\Bouncer;
use CrowdSecBouncer\Constants;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

function getCacheAdapterInstance(): AbstractAdapter
{
    switch (get_option('crowdsec_cache_system')) {

        case CROWDSEC_CACHE_SYSTEM_PHPFS:
            return new PhpFilesAdapter('', 0, __DIR__ . '/.cache');

        case CROWDSEC_CACHE_SYSTEM_MEMCACHED:
            $memcachedDsn = get_option('crowdsec_memcached_dsn');
            if (empty($memcachedDsn)) {
                throw new WordpressCrowdsecBouncerException('Memcached selected but no DSN provided.');
            }
            return new MemcachedAdapter(MemcachedAdapter::createConnection($memcachedDsn));

        case CROWDSEC_CACHE_SYSTEM_REDIS:
            $redisDsn = get_option('crowdsec_redis_dsn');
            if (empty($redisDsn)) {
                throw new WordpressCrowdsecBouncerException('Redis selected but no DSN provided.');
            }
            return new RedisAdapter(RedisAdapter::createConnection($redisDsn));
    }
}

function getBouncerInstance(string $bouncingLevel): Bouncer
{
    // Parse Wordpress Options.

    $apiUrl = get_option('crowdsec_api_url');
    if (empty($apiUrl)) {
        throw new WordpressCrowdsecBouncerException('Bouncer enabled but no API URL provided');
    }
    $apiKey = get_option('crowdsec_api_key');
    if (empty($apiKey)) {
        throw new WordpressCrowdsecBouncerException('Bouncer enabled but no API key provided');
    }
    $isStreamMode = (bool)(int)get_option('crowdsec_stream_mode');
    $cleanIpCacheDuration = (int)get_option('crowdsec_clean_ip_cache_duration');
    $fallbackRemediation = get_option('crowdsec_fallback_remediation');

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
    }

    // Display Library log in debug mode
    $logger = null;
    if (WP_DEBUG) {
        $logger = new Logger('wordpress');
        $fileHandler = new RotatingFileHandler(__DIR__.'/crowdsec.log', 0, Logger::DEBUG);
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

function bounceCurrentIp()
{
    $ip = $_SERVER["REMOTE_ADDR"];

    function displayCaptchaPage($ip)
    {
        $captcha = new CaptchaBuilder;
        $_SESSION['phrase'] = $captcha->getPhrase();
        $img = $captcha->build()->inline();
        echo "<html>";
        echo "<form method=\"post\">";
        echo "<img src=\"$img\" />";
        echo "<input type=\"text\" name=\"phrase\" />";
        echo "<input type=\"submit\" />";
        echo "</form>";
        echo "</html>";
        wp_die("Please fill the captcha.");
    }

    function handleBanRemediation(Bouncer $bouncer, $ip)
    {
        die($bouncer->getDefault403Template());
    }

    function checkCaptcha(string $ip)
    {
        //error_log("crowdsec-wp: " . $ip . " is in captcha mode"); TODO P2 check how 

        $captchaCorrectlyFilled = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phrase']) && PhraseBuilder::comparePhrases($_SESSION['phrase'], $_POST['phrase']));
        if (!$captchaCorrectlyFilled) {
            $_SESSION["captchaResolved"] = false;
            echo "Invalid captcha!";// TODO P2 Improve error template.
            displayCaptchaPage($ip);
        }

        $_SESSION["captchaResolved"] = true;
        unset($_SESSION['phrase']);
    }

    function handleCaptchaRemediation($ip)
    {
        if (!isset($_SESSION["captchaResolved"]) || !$_SESSION["captchaResolved"]) {
            displayCaptchaPage($ip);
        }
        // TODO P3 handle the case the user fill a captcha then the remediation expires then a new captcha remediation is asked, while the PHP session is active no captcha will never be ask again.
    }

    function handleRemediation(string $remediation, string $ip, Bouncer $bouncer)
    {
        switch ($remediation) {
            case Constants::REMEDIATION_BYPASS:
                return;
            case Constants::REMEDIATION_CAPTCHA:
                handleCaptchaRemediation($bouncer, $ip);
                break;
            case Constants::REMEDIATION_BAN:
                handleBanRemediation($bouncer, $ip);
        }
    }

    // Control Captcha
    if (isset($_SESSION['phrase'])) {
        checkCaptcha($ip);
    }

    $bouncingLevel = get_option("crowdsec_bouncing_level");
    $shouldBounce = ($bouncingLevel !== CROWDSEC_BOUNCING_LEVEL_DISABLED);

    if ($shouldBounce) {
        try {
            $bouncer = getBouncerInstance($bouncingLevel);
            $remediation = $bouncer->getRemediationForIp($ip);
            handleRemediation($remediation, $ip, $bouncer);
        } catch (WordpressCrowdsecBouncerException $e) {
            // TODO log error for debug mode only.
        }
    }
}

/**
 * If there is any technical problem while bouncing, don't block the user. Bypass boucing and log the error.
 */
function safelyBounceCurrentIp()
{
    // TODO P3 check that every kind of errors are catched.
    try {
        bounceCurrentIp();
    } catch (\Exception $e) {
        if (WP_DEBUG) {
            throw $e;
        }
        // TODO P3 log error if something has been catched here
    }
}
