<?php

namespace CrowdSecBouncer;

use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

/** @var Bouncer|null */
$crowdSecBouncer = null;

/** @var AbstractAdapter|null */
$crowdSecCacheAdapterInstance = null;

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
class StandAloneBounce extends AbstractBounce implements IBounce
{
    /** @var AbstractAdapter */
    protected $cacheAdapter;

    public function init(array $crowdSecStandaloneBouncerConfig)
    {
        if (\PHP_SESSION_NONE === session_status()) {
            $this->session_name = session_name("crowdsec");
            session_start();
        }
        $this->settings = $crowdSecStandaloneBouncerConfig;
        $this->initLogger();
    }

    private function getCacheAdapterInstance(): AbstractAdapter
    {
        // Singleton for this function
        if ($this->cacheAdapter) {
            return $this->cacheAdapter;
        }

        $cacheSystem = $this->getStringSettings('cache_system');
        switch ($cacheSystem) {
            case Constants::CACHE_SYSTEM_PHPFS:
                $this->cacheAdapter = new PhpFilesAdapter('', 0, $this->getStringSettings('fs_cache_path'));
                break;

            case Constants::CACHE_SYSTEM_MEMCACHED:
                $memcachedDsn = $this->getStringSettings('memcached_dsn');
                if (empty($memcachedDsn)) {
                    throw new BouncerException('The selected cache technology is Memcached.'.
                    ' Please set a Memcached DSN or select another cache technology.');
                }

                $this->cacheAdapter = new MemcachedAdapter(MemcachedAdapter::createConnection($memcachedDsn));
                break;

            case Constants::CACHE_SYSTEM_REDIS:
                $redisDsn = $this->getStringSettings('redis_dsn');
                if (empty($redisDsn)) {
                    throw new BouncerException('The selected cache technology is Redis.'.
                    ' Please set a Redis DSN or select another cache technology.');
                }

                try {
                    $this->cacheAdapter = new RedisAdapter(RedisAdapter::createConnection($redisDsn));
                } catch (InvalidArgumentException $e) {
                    throw new BouncerException('Error when connecting to Redis.'.
                    ' Please fix the Redis DSN or select another cache technology.');
                }

                break;
            default:
                throw new BouncerException('Unknow selected cache technology.');
        }

        return $this->cacheAdapter;
    }

    /**
     * @return Bouncer get the bouncer instance
     */
    public function getBouncerInstance(): Bouncer
    {
        // Singleton for this function
        if ($this->bouncer) {
            return $this->bouncer;
        }

        // Parse options.

        if (empty($this->getStringSettings('api_url'))) {
            throw new BouncerException('Bouncer enabled but no API URL provided');
        }
        if (empty($this->getStringSettings('api_key'))) {
            throw new BouncerException('Bouncer enabled but no API key provided');
        }
        $isStreamMode = $this->getStringSettings('stream_mode');
        $cleanIpCacheDuration = (int) $this->getStringSettings('clean_ip_cache_duration');
        $badIpCacheDuration = (int) $this->getStringSettings('bad_ip_cache_duration');
        $fallbackRemediation = $this->getStringSettings('fallback_remediation');
        $bouncingLevel = $this->getStringSettings('bouncing_level');

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
            throw new BouncerException("Unknown $bouncingLevel");
    }

        // Instanciate the bouncer
        try {
            $this->cacheAdapter = $this->getCacheAdapterInstance();
        } catch (InvalidArgumentException $e) {
            throw new BouncerException($e->getMessage());
        }

        $apiUserAgent = 'Standalone CrowdSec PHP Bouncer/'.Constants::VERSION;

        $this->bouncer = new Bouncer($this->cacheAdapter, $this->logger);
        $this->bouncer->configure([
            'api_key' => $this->getStringSettings('api_key'),
            'api_url' => $this->getStringSettings('api_url'),
            'api_user_agent' => $apiUserAgent,
            'live_mode' => !$isStreamMode,
            'max_remediation_level' => $maxRemediationLevel,
            'fallback_remediation' => $fallbackRemediation,
            'cache_expiration_for_clean_ip' => $cleanIpCacheDuration,
            'cache_expiration_for_bad_ip' => $badIpCacheDuration,
        ], $this->cacheAdapter);

        return $this->bouncer;
    }

    public function initLogger(): void
    {
        $this->initLoggerHelper($this->getStringSettings('log_directory_path'), 'php_standalone_bouncer');
    }

    /**
     * @return string Ex: "X-Forwarded-For"
     */
    public function getHttpRequestHeader(string $name): ?string
    {
        $headerName = 'HTTP_'.str_replace('-', '_', strtoupper($name));
        if (!\array_key_exists($headerName, $_SERVER)) {
            return null;
        }

        return $_SERVER[$headerName];
    }

    /**
     * @return string The current IP, even if it's the IP of a proxy
     */
    public function getRemoteIp(): string
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @return string The current IP, even if it's the IP of a proxy
     */
    public function getHttpMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return array ['hide_crowdsec_mentions': bool, color:[text:['primary' : string, 'secondary' : string, 'button' : string, 'error_message : string' ...]]] (returns an array of option required to build the captcha wall template)
     */
    public function getCaptchaWallOptions(): array
    {
        return [
            'hide_crowdsec_mentions' => (bool) $this->getStringSettings('hide_mentions'),
            'color' => [
              'text' => [
                'primary' => htmlspecialchars_decode($this->getStringSettings('theme_color_text_primary'), \ENT_QUOTES),
                'secondary' => htmlspecialchars_decode($this->getStringSettings('theme_color_text_secondary'), \ENT_QUOTES),
                'button' => htmlspecialchars_decode($this->getStringSettings('theme_color_text_button'), \ENT_QUOTES),
                'error_message' => htmlspecialchars_decode($this->getStringSettings('theme_color_text_error_message'), \ENT_QUOTES),
              ],
              'background' => [
                'page' => htmlspecialchars_decode($this->getStringSettings('theme_color_background_page'), \ENT_QUOTES),
                'container' => htmlspecialchars_decode($this->getStringSettings('theme_color_background_container'), \ENT_QUOTES),
                'button' => htmlspecialchars_decode($this->getStringSettings('theme_color_background_button'), \ENT_QUOTES),
                'button_hover' => htmlspecialchars_decode($this->getStringSettings('theme_color_background_button_hover'), \ENT_QUOTES),
              ],
            ],
            'text' => [
              'captcha_wall' => [
                'tab_title' => htmlspecialchars_decode($this->getStringSettings('theme_text_captcha_wall_tab_title'), \ENT_QUOTES),
                'title' => htmlspecialchars_decode($this->getStringSettings('theme_text_captcha_wall_title'), \ENT_QUOTES),
                'subtitle' => htmlspecialchars_decode($this->getStringSettings('theme_text_captcha_wall_subtitle'), \ENT_QUOTES),
                'refresh_image_link' => htmlspecialchars_decode($this->getStringSettings('theme_text_captcha_wall_refresh_image_link'), \ENT_QUOTES),
                'captcha_placeholder' => htmlspecialchars_decode($this->getStringSettings('theme_text_captcha_wall_captcha_placeholder'), \ENT_QUOTES),
                'send_button' => htmlspecialchars_decode($this->getStringSettings('theme_text_captcha_wall_send_button'), \ENT_QUOTES),
                'error_message' => htmlspecialchars_decode($this->getStringSettings('theme_text_captcha_wall_error_message'), \ENT_QUOTES),
                'footer' => htmlspecialchars_decode($this->getStringSettings('theme_text_captcha_wall_footer'), \ENT_QUOTES),
              ],
            ],
            'custom_css' => $this->getStringSettings('theme_custom_css'),
          ];
    }

    /**
     * @return array ['hide_crowdsec_mentions': bool, color:[text:['primary' : string, 'secondary' : string, 'error_message : string' ...]]] (returns an array of option required to build the ban wall template)
     */
    public function getBanWallOptions(): array
    {
        return [
            'hide_crowdsec_mentions' => (bool) $this->getStringSettings('hide_mentions'),
            'color' => [
              'text' => [
                'primary' => htmlspecialchars_decode($this->getStringSettings('theme_color_text_primary'), \ENT_QUOTES),
                'secondary' => htmlspecialchars_decode($this->getStringSettings('theme_color_text_secondary'), \ENT_QUOTES),
                'error_message' => htmlspecialchars_decode($this->getStringSettings('theme_color_text_error_message'), \ENT_QUOTES),
              ],
              'background' => [
                'page' => htmlspecialchars_decode($this->getStringSettings('theme_color_background_page'), \ENT_QUOTES),
                'container' => htmlspecialchars_decode($this->getStringSettings('theme_color_background_container'), \ENT_QUOTES),
                'button' => htmlspecialchars_decode($this->getStringSettings('theme_color_background_button'), \ENT_QUOTES),
                'button_hover' => htmlspecialchars_decode($this->getStringSettings('theme_color_background_button_hover'), \ENT_QUOTES),
              ],
            ],
            'text' => [
              'ban_wall' => [
                'tab_title' => htmlspecialchars_decode($this->getStringSettings('theme_text_ban_wall_tab_title'), \ENT_QUOTES),
                'title' => htmlspecialchars_decode($this->getStringSettings('theme_text_ban_wall_title'), \ENT_QUOTES),
                'subtitle' => htmlspecialchars_decode($this->getStringSettings('theme_text_ban_wall_subtitle'), \ENT_QUOTES),
                'footer' => htmlspecialchars_decode($this->getStringSettings('theme_text_ban_wall_footer'), \ENT_QUOTES),
              ],
            ],
            'custom_css' => htmlspecialchars_decode($this->getStringSettings('theme_custom_css'), \ENT_QUOTES),
          ];
    }

    /**
     * @return [[string, string], ...] Returns IP ranges to trust as proxies as an array of comparables ip bounds
     */
    public function getTrustForwardedIpBoundsList(): array
    {
        return $this->getArraySettings('trust_ip_forward_array');
    }

    /**
     * Return a session variable, null if not set.
     */
    public function getSessionVariable(string $name): ?string
    {
        if (!isset($_SESSION[$name])) {
            return null;
        }

        return $_SESSION[$name];
    }

    /**
     * Set a session variable.
     */
    public function setSessionVariable(string $name, $value): void
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Unset a session variable, throw an error if this does not exists.
     *
     * @return void;
     */
    public function unsetSessionVariable(string $name): void
    {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
    }

    /**
     * Get the value of a posted field.
     */
    public function getPostedVariable(string $name): ?string
    {
        if (!isset($_POST[$name])) {
            return null;
        }

        return $_POST[$name];
    }

    /**
     * If the current IP should be bounced or not, matching custom business rules.
     */
    public function shouldBounceCurrentIp(): bool
    {
        // Don't bounce favicon calls or when the config is invalid.
        if ('/favicon.ico' === $_SERVER['REQUEST_URI'] || !$this->isConfigValid()) {
            return false;
        }

        $bouncingDisabled = (Constants::BOUNCING_LEVEL_DISABLED === $this->getStringSettings('bouncing_level'));
        if ($bouncingDisabled) {
            return false;
        }

        return true;
    }

    /**
     * Send HTTP response.
     */
    public function sendResponse(?string $body, int $statusCode = 200): void
    {
        switch ($statusCode) {
            case 200:
                header('HTTP/1.0 200 OK');
                break;
            case 401:
                header('HTTP/1.0 401 Unauthorized');
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
                break;
            case 403:
                header('HTTP/1.0 403 Forbidden');
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
                break;
            default:
                throw new BouncerException("Unhandled code $statusCode");
        }
        if (null !== $body) {
            echo $body;
        }
        die();
    }

    public function safelyBounce(): void
    {
        // If there is any technical problem while bouncing, don't block the user. Bypass bouncing and log the error.
        try {
            set_error_handler(function ($errno, $errstr) {
                throw new BouncerException("$errstr (Error level: $errno)");
            });
            $this->run();
            restore_error_handler();
        } catch (\Exception $e) {
            $this->logger->error('', [
                'type' => 'EXCEPTION_WHILE_BOUNCING',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            if ($this->displayErrors) {
                throw $e;
            }
        } finally {
            if (\PHP_SESSION_NONE !== session_status()) {
                session_write_close();
                session_name($this->session_name);
            }
        }
    }

    public function isConfigValid(): bool
    {
        $issues = ['errors' => [], 'warnings' => []];

        $bouncingLevel = $this->getStringSettings('bouncing_level');
        $shouldBounce = (Constants::BOUNCING_LEVEL_DISABLED !== $bouncingLevel);

        if ($shouldBounce) {
            $apiUrl = $this->getStringSettings('api_url');
            if (empty($apiUrl)) {
                $issues['errors'][] = [
                'type' => 'INCORRECT_API_URL',
                'message' => 'Bouncer enabled but no API URL provided',
            ];
            }

            $apiKey = $this->getStringSettings('api_key');
            if (empty($apiKey)) {
                $issues['errors'][] = [
                'type' => 'INCORRECT_API_KEY',
                'message' => 'Bouncer enabled but no API key provided',
            ];
            }

            try {
                $this->getCacheAdapterInstance();
            } catch (BouncerException $e) {
                $issues['errors'][] = [
                'type' => 'CACHE_CONFIG_ERROR',
                'message' => $e->getMessage(),
            ];
            }
        }

        return !\count($issues['errors']) && !\count($issues['warnings']);
    }
}
