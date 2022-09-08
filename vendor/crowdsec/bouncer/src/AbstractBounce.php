<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

use IPLib\Factory;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * The class that apply a bounce.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2021+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractBounce implements IBounce
{
    /** @var array */
    protected $settings = [];

    /** @var LoggerInterface|null */
    protected $logger;

    /** @var Bouncer|null */
    protected $bouncer;

    protected function getIntegerSettings(string $name): int
    {
        return !empty($this->settings[$name]) ? (int)$this->settings[$name] : 0;
    }

    protected function getBoolSettings(string $name): bool
    {
        return !empty($this->settings[$name]) && $this->settings[$name];
    }

    protected function getStringSettings(string $name): string
    {
        return !empty($this->settings[$name]) ? (string)$this->settings[$name] : '';
    }

    protected function getArraySettings(string $name): array
    {
        return !empty($this->settings[$name]) ? (array)$this->settings[$name] : [];
    }

    /**
     * @throws BouncerException
     */
    protected function prepareBouncerConfigs(): array
    {
        $bouncingLevel = $this->getStringSettings('bouncing_level');
        $apiTimeout = $this->getIntegerSettings('api_timeout');

        // Compute max remediation level
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
        $authType = $this->getStringSettings('auth_type');

        return [
            // LAPI connection
            'auth_type' => $authType ?: Constants::AUTH_KEY,
            'tls_cert_path' => $this->getStringSettings('tls_cert_path'),
            'tls_key_path' => $this->getStringSettings('tls_key_path'),
            'tls_verify_peer' => $this->getBoolSettings('tls_verify_peer'),
            'tls_ca_cert_path' => $this->getStringSettings('tls_ca_cert_path'),
            'api_key' => $this->getStringSettings('api_key'),
            'api_url' => $this->getStringSettings('api_url'),
            'api_user_agent' => $this->getStringSettings('api_user_agent'),
            'api_timeout' => $apiTimeout > 0 ? $apiTimeout : Constants::API_TIMEOUT,
            'use_curl' => $this->getBoolSettings('use_curl'),
            // Debug
            'debug_mode' => $this->getBoolSettings('debug_mode'),
            'log_directory_path' => $this->getStringSettings('log_directory_path'),
            'forced_test_ip' => $this->getStringSettings('forced_test_ip'),
            'forced_test_forwarded_ip' => $this->getStringSettings('forced_test_forwarded_ip'),
            'display_errors' => $this->getBoolSettings('display_errors'),
            // Bouncer
            'bouncing_level' => $bouncingLevel,
            'trust_ip_forward_array' => $this->getArraySettings('trust_ip_forward_array'),
            'fallback_remediation' => $this->getStringSettings('fallback_remediation'),
            'max_remediation_level' => $maxRemediationLevel,
            // Cache settings
            'stream_mode' => $this->getBoolSettings('stream_mode'),
            'cache_system' => $this->getStringSettings('cache_system'),
            'fs_cache_path' => $this->getStringSettings('fs_cache_path'),
            'redis_dsn' => $this->getStringSettings('redis_dsn'),
            'memcached_dsn' => $this->getStringSettings('memcached_dsn'),
            'clean_ip_cache_duration' => $this->getIntegerSettings('clean_ip_cache_duration'),
            'bad_ip_cache_duration' => $this->getIntegerSettings('bad_ip_cache_duration'),
            'captcha_cache_duration' => $this->getIntegerSettings('captcha_cache_duration'),
            'geolocation_cache_duration' => $this->getIntegerSettings('geolocation_cache_duration'),
            // Geolocation
            'geolocation' => $this->getArraySettings('geolocation'),
        ];
    }

    /**
     * Run a bounce.
     *
     * @return void
     * @throws CacheException
     * @throws InvalidArgumentException|BouncerException
     */
    public function run(): void
    {
        $this->bounceCurrentIp();
    }

    /**
     * @param array $configs
     * @param string $loggerName
     * @return void
     */
    protected function initLoggerHelper(array $configs, string $loggerName): void
    {
        // Singleton for this function
        if ($this->logger) {
            return;
        }

        $this->logger = new Logger($loggerName);
        $logDir = $configs['log_directory_path'] ?? __DIR__ . '/.logs';
        if (empty($configs['disable_prod_log'])) {
            $logPath = $logDir . '/prod.log';
            $fileHandler = new RotatingFileHandler($logPath, 0, Logger::INFO);
            $fileHandler->setFormatter(new LineFormatter("%datetime%|%level%|%context%\n"));
            $this->logger->pushHandler($fileHandler);
        }

        // Set custom readable logger when debug=true
        if (!empty($configs['debug_mode'])) {
            $debugLogPath = $logDir . '/debug.log';
            $debugFileHandler = new RotatingFileHandler($debugLogPath, 0, Logger::DEBUG);
            $debugFileHandler->setFormatter(new LineFormatter("%datetime%|%level%|%context%\n"));
            $this->logger->pushHandler($debugFileHandler);
        }
    }

    /**
     * Handle X-Forwarded-For HTTP header to retrieve the IP to bounce
     *
     * @param string $ip
     * @param array $configs
     * @return string
     */
    protected function handleForwardedFor(string $ip, array $configs): string
    {
        $forwardedIp = null;
        if (empty($configs['forced_test_forwarded_ip'])) {
            $XForwardedForHeader = $this->getHttpRequestHeader('X-Forwarded-For');
            if (null !== $XForwardedForHeader) {
                $ipList = array_map('trim', array_values(array_filter(explode(',', $XForwardedForHeader))));
                $forwardedIp = end($ipList);
            }
        } elseif ($configs['forced_test_forwarded_ip'] === Constants::X_FORWARDED_DISABLED) {
            if ($this->logger) {
                $this->logger->debug('', [
                    'type' => 'DISABLED_X_FORWARDED_FOR_USAGE',
                    'original_ip' => $ip,
                ]);
            }
        } else {
            $forwardedIp = (string) $configs['forced_test_forwarded_ip'];
        }

        if (is_string($forwardedIp) && $this->shouldTrustXforwardedFor($ip)) {
            $ip = $forwardedIp;
        } elseif ($this->logger) {
            $this->logger->warning('', [
                'type' => 'NON_AUTHORIZED_X_FORWARDED_FOR_USAGE',
                'original_ip' => $ip,
                'x_forwarded_for_ip' => is_string($forwardedIp) ? $forwardedIp : 'type not as expected',
            ]);
        }
        return $ip;
    }

    /**
     * Bounce process
     *
     * @return void
     * @throws BouncerException
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    protected function bounceCurrentIp(): void
    {
        if (!$this->bouncer) {
            throw new BouncerException('Bouncer must be instantiated to bounce an IP.');
        }
        $configs = $this->bouncer->getConfigs();
        // Retrieve the current IP (even if it is a proxy IP) or a testing IP
        $ip = !empty($configs['forced_test_ip']) ? $configs['forced_test_ip'] : $this->getRemoteIp();
        $ip = $this->handleForwardedFor($ip, $configs);
        $remediation = $this->bouncer->getRemediationForIp($ip);
        $this->handleRemediation($remediation, $ip);
    }

    protected function shouldTrustXforwardedFor(string $ip): bool
    {
        $parsedAddress = Factory::parseAddressString($ip, 3);
        if (null === $parsedAddress) {
            if ($this->logger) {
                $this->logger->warning('', [
                    'type' => 'INVALID_INPUT_IP',
                    'ip' => $ip,
                ]);
            }

            return false;
        }
        $comparableAddress = $parsedAddress->getComparableString();

        foreach ($this->getTrustForwardedIpBoundsList() as $comparableIpBounds) {
            if ($comparableAddress >= $comparableIpBounds[0] && $comparableAddress <= $comparableIpBounds[1]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws InvalidArgumentException|BouncerException
     */
    protected function displayCaptchaWall(string $ip): void
    {
        $options = $this->getCaptchaWallOptions();
        $captchaVariables = $this->getIpVariables(
            Constants::CACHE_TAG_CAPTCHA,
            ['crowdsec_captcha_resolution_failed', 'crowdsec_captcha_inline_image'],
            $ip
        );
        $body = Bouncer::getCaptchaHtmlTemplate(
            (bool)$captchaVariables['crowdsec_captcha_resolution_failed'],
            (string)$captchaVariables['crowdsec_captcha_inline_image'],
            '',
            $options
        );
        $this->sendResponse($body, 401);
    }

    protected function handleBanRemediation(): void
    {
        $options = $this->getBanWallOptions();
        $body = Bouncer::getAccessForbiddenHtmlTemplate($options);
        $this->sendResponse($body, 403);
    }

    /**
     * @param string $ip
     * @return void
     * @throws InvalidArgumentException
     * @throws CacheException|BouncerException
     */
    protected function handleCaptchaResolutionForm(string $ip)
    {
        $cachedCaptchaVariables = $this->getIpVariables(
            Constants::CACHE_TAG_CAPTCHA,
            [
                'crowdsec_captcha_has_to_be_resolved',
                'crowdsec_captcha_phrase_to_guess',
                'crowdsec_captcha_resolution_redirect',
            ],
            $ip
        );
        // Early return if no captcha has to be resolved or if captcha already resolved.
        if (\in_array($cachedCaptchaVariables['crowdsec_captcha_has_to_be_resolved'], [null, false])) {
            return;
        }

        // Early return if no form captcha form has been filled.
        if ('POST' !== $this->getHttpMethod() || null === $this->getPostedVariable('crowdsec_captcha')) {
            return;
        }

        // Handle image refresh.
        if (null !== $this->getPostedVariable('refresh') && (int)$this->getPostedVariable('refresh')) {
            // Generate new captcha image for the user
            $captchaCouple = Bouncer::buildCaptchaCouple();
            $captchaVariables = [
                'crowdsec_captcha_phrase_to_guess' => $captchaCouple['phrase'],
                'crowdsec_captcha_inline_image' => $captchaCouple['inlineImage'],
                'crowdsec_captcha_resolution_failed' => false,
            ];
            $this->setIpVariables(Constants::CACHE_TAG_CAPTCHA, $captchaVariables, $ip);

            return;
        }

        // Handle a captcha resolution try
        if (
            null !== $this->getPostedVariable('phrase')
            && null !== $cachedCaptchaVariables['crowdsec_captcha_phrase_to_guess']
        ) {
            if (!$this->bouncer) {
                throw new BouncerException('Bouncer must be instantiated to check captcha.');
            }
            if (
                $this->bouncer->checkCaptcha(
                    (string)$cachedCaptchaVariables['crowdsec_captcha_phrase_to_guess'],
                    (string)$this->getPostedVariable('phrase'),
                    $ip
                )
            ) {
                // User has correctly filled the captcha
                $this->setIpVariables(
                    Constants::CACHE_TAG_CAPTCHA,
                    ['crowdsec_captcha_has_to_be_resolved' => false],
                    $ip
                );
                $unsetVariables = [
                    'crowdsec_captcha_phrase_to_guess',
                    'crowdsec_captcha_inline_image',
                    'crowdsec_captcha_resolution_failed',
                    'crowdsec_captcha_resolution_redirect',
                ];
                $this->unsetIpVariables(Constants::CACHE_TAG_CAPTCHA, $unsetVariables, $ip);
                $redirect = $cachedCaptchaVariables['crowdsec_captcha_resolution_redirect'] ?? '/';
                header("Location: $redirect");
                exit(0);
            } else {
                // The user failed to resolve the captcha.
                $this->setIpVariables(
                    Constants::CACHE_TAG_CAPTCHA,
                    ['crowdsec_captcha_resolution_failed' => true],
                    $ip
                );
            }
        }
    }

    /**
     * @param string $ip
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws CacheException|BouncerException
     */
    protected function handleCaptchaRemediation(string $ip)
    {
        // Check captcha resolution form
        $this->handleCaptchaResolutionForm($ip);
        $cachedCaptchaVariables = $this->getIpVariables(
            Constants::CACHE_TAG_CAPTCHA,
            ['crowdsec_captcha_has_to_be_resolved'],
            $ip
        );
        $mustResolve = false;
        if (null === $cachedCaptchaVariables['crowdsec_captcha_has_to_be_resolved']) {
            // Set up the first captcha remediation.
            $mustResolve = true;
            $captchaCouple = Bouncer::buildCaptchaCouple();
            $captchaVariables = [
                'crowdsec_captcha_phrase_to_guess' => $captchaCouple['phrase'],
                'crowdsec_captcha_inline_image' => $captchaCouple['inlineImage'],
                'crowdsec_captcha_has_to_be_resolved' => true,
                'crowdsec_captcha_resolution_failed' => false,
                'crowdsec_captcha_resolution_redirect' => 'POST' === $this->getHttpMethod() &&
                                                          !empty($_SERVER['HTTP_REFERER'])
                    ? $_SERVER['HTTP_REFERER'] : '/',
            ];
            $this->setIpVariables(Constants::CACHE_TAG_CAPTCHA, $captchaVariables, $ip);
        }

        // Display captcha page if this is required.
        if ($cachedCaptchaVariables['crowdsec_captcha_has_to_be_resolved'] || $mustResolve) {
            $this->displayCaptchaWall($ip);
        }
    }

    /**
     * Handle remediation for some IP.
     *
     * @param string $remediation
     * @param string $ip
     * @return void
     * @throws InvalidArgumentException
     * @throws CacheException|BouncerException
     */
    protected function handleRemediation(string $remediation, string $ip)
    {
        switch ($remediation) {
            case Constants::REMEDIATION_CAPTCHA:
                $this->handleCaptchaRemediation($ip);
                break;
            case Constants::REMEDIATION_BAN:
                $this->handleBanRemediation();
                break;
            case Constants::REMEDIATION_BYPASS:
            default:
        }
    }

    /**
     * Return cached variables associated to an IP.
     *
     * @param string $cacheTag
     * @param array $names
     * @param string $ip
     * @return array
     * @throws InvalidArgumentException|BouncerException
     */
    public function getIpVariables(string $cacheTag, array $names, string $ip): array
    {
        if (!$this->bouncer) {
            throw new BouncerException('Bouncer must be instantiated to get cache data.');
        }
        $apiCache = $this->bouncer->getApiCache();

        return $apiCache->getIpVariables($cacheTag, $names, $ip);
    }

    /**
     * Set a ip variable.
     *
     * @param string $cacheTag
     * @param array $pairs
     * @param string $ip
     * @return void
     * @throws InvalidArgumentException
     * @throws CacheException|BouncerException
     */
    public function setIpVariables(string $cacheTag, array $pairs, string $ip): void
    {
        if (!$this->bouncer) {
            throw new BouncerException('Bouncer must be instantiated to set cache data.');
        }
        $apiCache = $this->bouncer->getApiCache();
        $apiCache->setIpVariables($cacheTag, $pairs, $ip);
    }

    /**
     * Unset ip variables.
     *
     * @param string $cacheTag
     * @param array $names
     * @param string $ip
     * @return void
     * @throws InvalidArgumentException
     * @throws CacheException|BouncerException
     */
    public function unsetIpVariables(string $cacheTag, array $names, string $ip): void
    {
        if (!$this->bouncer) {
            throw new BouncerException('Bouncer must be instantiated to unset cache data.');
        }
        $apiCache = $this->bouncer->getApiCache();
        $apiCache->unsetIpVariables($cacheTag, $names, $ip);
    }
}
