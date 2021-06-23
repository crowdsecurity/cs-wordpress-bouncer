<?php

use CrowdSecBouncer\AbstractBounce;
use CrowdSecBouncer\Bouncer;
use CrowdSecBouncer\Constants;
use CrowdSecBouncer\IBounce;

/**
 * The class that apply a bounce.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Bounce extends AbstractBounce implements IBounce
{
    public function init(array $crowdSecConfig): bool
    {
        $this->settings = $crowdSecConfig;
        $this->initLogger();

        return true;
    }

    protected function getSettings(string $name)
    {
        if (!array_key_exists($name, $this->settings)) {
            $this->settings[$name] = get_option($name);
        }

        return $this->settings[$name];
    }

    protected function escape(string $value)
    {
        return esc_attr($value);
    }

    protected function specialcharsDecodeEntQuotes(string $value)
    {
        return wp_specialchars_decode($value, \ENT_QUOTES);
    }

    /**
     * @return Bouncer get the bouncer instance
     */
    public function getBouncerInstance(): Bouncer
    {
        $apiUrl = $this->escape($this->getSettings('crowdsec_api_url'));
        $apiKey = $this->escape($this->getSettings('crowdsec_api_key'));
        $isStreamMode = !empty($this->getSettings('crowdsec_stream_mode'));
        $cleanIpCacheDuration = (int) $this->getSettings('crowdsec_clean_ip_cache_duration');
        $badIpCacheDuration = (int) $this->getSettings('crowdsec_bad_ip_cache_duration');
        $fallbackRemediation = $this->escape($this->getSettings('crowdsec_fallback_remediation'));
        $bouncingLevel = $this->escape($this->getSettings('crowdsec_bouncing_level'));
        $crowdSecBouncerUserAgent = CROWDSEC_BOUNCER_USER_AGENT;
        $crowdSecLogPath = CROWDSEC_LOG_PATH;
        $crowdSecDebugLogPath = CROWDSEC_DEBUG_LOG_PATH;
        $debugMode = (bool) WP_DEBUG;

        $this->logger = getStandAloneCrowdSecLoggerInstance($crowdSecLogPath, $debugMode, $crowdSecDebugLogPath);
        $this->bouncer = getBouncerInstanceStandAlone($apiUrl, $apiKey, $isStreamMode, $cleanIpCacheDuration, $badIpCacheDuration, $fallbackRemediation, $bouncingLevel, $crowdSecBouncerUserAgent, $this->logger);

        return $this->bouncer;
    }

    /**
     * @return string Ex: "X-Forwarded-For"
     */
    public function getHttpRequestHeader(string $name): ?string
    {
        $headerName = 'HTTP_'.str_replace('-', '_', strtoupper($name));
        if (!array_key_exists($headerName, $_SERVER)) {
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
            'hide_crowdsec_mentions' => (bool) $this->getSettings('crowdsec_hide_mentions'),
            'color' => [
              'text' => [
                'primary' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_text_primary')),
                'secondary' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_text_secondary')),
                'button' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_text_button')),
                'error_message' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_text_error_message')),
              ],
              'background' => [
                'page' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_background_page')),
                'container' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_background_container')),
                'button' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_background_button')),
                'button_hover' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_background_button_hover')),
              ],
            ],
            'text' => [
              'captcha_wall' => [
                'tab_title' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_text_captcha_wall_tab_title')),
                'title' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_text_captcha_wall_title')),
                'subtitle' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_text_captcha_wall_subtitle')),
                'refresh_image_link' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_text_captcha_wall_refresh_image_link')),
                'captcha_placeholder' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_text_captcha_wall_captcha_placeholder')),
                'send_button' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_text_captcha_wall_send_button')),
                'error_message' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_text_captcha_wall_error_message')),
                'footer' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_text_captcha_wall_footer')),
              ],
            ],
            'custom_css' => $this->getSettings('crowdsec_theme_custom_css'),
          ];
    }

    /**
     * @return array ['hide_crowdsec_mentions': bool, color:[text:['primary' : string, 'secondary' : string, 'error_message : string' ...]]] (returns an array of option required to build the ban wall template)
     */
    public function getBanWallOptions(): array
    {
        return [
            'hide_crowdsec_mentions' => (bool) $this->getSettings('crowdsec_hide_mentions'),
            'color' => [
              'text' => [
                'primary' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_text_primary')),
                'secondary' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_text_secondary')),
                'error_message' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_text_error_message')),
              ],
              'background' => [
                'page' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_background_page')),
                'container' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_background_container')),
                'button' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_background_button')),
                'button_hover' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_color_background_button_hover')),
              ],
            ],
            'text' => [
              'ban_wall' => [
                'tab_title' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_text_ban_wall_tab_title')),
                'title' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_text_ban_wall_title')),
                'subtitle' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_text_ban_wall_subtitle')),
                'footer' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_text_ban_wall_footer')),
              ],
            ],
            'custom_css' => $this->specialcharsDecodeEntQuotes($this->getSettings('crowdsec_theme_custom_css')),
          ];
    }

    /**
     * @return [[string, string], ...] Returns IP ranges to trust as proxies as an array of comparables ip bounds
     */
    public function getTrustForwardedIpBoundsList(): array
    {
        return $this->getSettings('crowdsec_trust_ip_forward_array');
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
    public function unsetSessionVariable(string $name): void /* throw */
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
        // Don't bounce favicon calls.
        if ('/favicon.ico' === $_SERVER['REQUEST_URI']) {
            return false;
        }

        $shouldNotBounceWpAdmin = !empty($this->getSettings('crowdsec_public_website_only'));
        // when the "crowdsec_public_website_only" is disabled...
        if ($shouldNotBounceWpAdmin) {
            // ...don't bounce back office pages
            if (is_admin()) {
                return false;
            }
            // ...don't bounce wp-login and wp-cron pages
            if (in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-cron.php'])) {
                return false;
            }
        }

        if (!$this->isConfigValid()) {
            // We bounce only if plugin config is valid
            return false;
        }

        $bouncingDisabled = (Constants::BOUNCING_LEVEL_DISABLED === $this->escape($this->getSettings('crowdsec_bouncing_level')));
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
                break;
            case 403:
                header('HTTP/1.0 403 Forbidden');
                break;
            default:
                throw new Exception("Unhandled code ${statusCode}");
        }
        if (null !== $body) {
            echo $body;
        }
        die();
    }

    public function safelyBounce(): void
    {
        // If there is any technical problem while bouncing, don't block the user. Bypass boucing and log the error.
        try {
            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
            });
            $this->run();
            restore_error_handler();
        } catch (\Exception $e) {
            $this->logger->error('', [
                'type' => 'WP_EXCEPTION_WHILE_BOUNCING',
                'messsage' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            if (WP_DEBUG) {
                throw $e;
            }
        }
    }

    public function isConfigValid(): bool
    {
        $issues = ['errors' => [], 'warnings' => []];

        $bouncingLevel = $this->escape($this->getSettings('crowdsec_bouncing_level'));
        $shouldBounce = (Constants::BOUNCING_LEVEL_DISABLED !== $bouncingLevel);

        if ($shouldBounce) {
            $apiUrl = $this->escape($this->getSettings('crowdsec_api_url'));
            if (empty($apiUrl)) {
                $issues['errors'][] = [
                'type' => 'INCORRECT_API_URL',
                'message' => 'Bouncer enabled but no API URL provided',
            ];
            }

            $apiKey = $this->escape($this->getSettings('crowdsec_api_key'));
            if (empty($apiKey)) {
                $issues['errors'][] = [
                'type' => 'INCORRECT_API_KEY',
                'message' => 'Bouncer enabled but no API key provided',
            ];
            }

            try {
                $cacheSystem = $this->escape($this->getSettings('crowdsec_cache_system'));
                $memcachedDsn = $this->escape($this->getSettings('crowdsec_memcached_dsn'));
                $redisDsn = $this->escape($this->getSettings('crowdsec_redis_dsn'));
                $fsCachePath = CROWDSEC_CACHE_PATH;
                getCacheAdapterInstanceStandAlone($cacheSystem, $memcachedDsn, $redisDsn, $fsCachePath);
            } catch (WordpressCrowdSecBouncerException $e) {
                $issues['errors'][] = [
                'type' => 'CACHE_CONFIG_ERROR',
                'message' => $e->getMessage(),
            ];
            }
        }

        return !count($issues['errors']) && !count($issues['warnings']);
    }

    public function initLogger(): void
    {
        $debugMode = (bool) WP_DEBUG;
        $crowdSecLogPath = CROWDSEC_LOG_PATH;
        $crowdSecDebugLogPath = CROWDSEC_DEBUG_LOG_PATH;
        $this->logger = getStandAloneCrowdSecLoggerInstance($crowdSecLogPath, $debugMode, $crowdSecDebugLogPath);
    }
}
