<?php

declare(strict_types=1);

namespace CrowdSecWordPressBouncer;

use CrowdSec\RemediationEngine\CacheStorage\CacheStorageException;
use CrowdSec\RemediationEngine\LapiRemediation;
use CrowdSec\Common\Logger\FileLog;
use CrowdSecBouncer\AbstractBouncer;
use CrowdSecBouncer\BouncerException;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/Constants.php';

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
class Bouncer extends AbstractBouncer
{

    protected $shouldNotBounceWpAdmin = true;

    protected $baseFilesPath;

    /**
     * @throws BouncerException
     * @throws CacheStorageException
     */
    public function __construct(array $configs, LoggerInterface $logger = null)
    {
        $this->shouldNotBounceWpAdmin = (bool)($configs['crowdsec_public_website_only'] ?? true);
        $configs = $this->handleRawConfigs($configs);
        $logConfigs = array_merge($configs, ['no_rotation' => true]);
        $this->logger = $logger ?: new FileLog($logConfigs, 'wordpress_bouncer');
        $configs['user_agent_version'] = Constants::VERSION;
        $client = $this->handleClient($configs, $this->logger);
        $cache = $this->handleCache($configs, $this->logger);
        $remediation = new LapiRemediation($configs, $client, $cache, $this->logger);

        parent::__construct($configs, $remediation, $this->logger);
    }

    private function renderTemplate(string $templatePath,array $configs = []): string
    {
        ob_start();
        $config = array_merge($this->configs, $configs);
        include __DIR__ . '/templates/' . $templatePath;
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    protected function getBanHtml(): string
    {
        return $this->renderTemplate('ban-wall.php');
    }

    protected function getCaptchaHtml(
        bool $error,
        string $captchaImageSrc,
        string $captchaResolutionFormUrl
    ): string {
        $configs = [
            'error' => $error,
            'captcha_img' => $captchaImageSrc,
            'captcha_resolution_url' => $captchaResolutionFormUrl,
        ];


        return $this->renderTemplate('captcha-wall.php', $configs);
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES, 'UTF-8');
    }

    protected function specialcharsDecodeEntQuotes(string $value): string
    {
        return htmlspecialchars_decode($value, \ENT_QUOTES);
    }

    private function getBaseFilesPath(): string
    {
        if ($this->baseFilesPath === null) {
            $result = Constants::DEFAULT_BASE_FILE_PATH;

            if (function_exists('wp_upload_dir')) {
                if(is_multisite()){
                    $mainSiteId = get_main_site_id();
                    switch_to_blog($mainSiteId);
                }
                $dir = wp_upload_dir(null, false);
                if (is_array($dir) && array_key_exists('basedir', $dir)) {
                    $result = $dir['basedir'] . '/crowdsec/';
                } elseif (defined('WP_CONTENT_DIR')) {
                    $result = WP_CONTENT_DIR . '/uploads/crowdsec/';
                }
                if(is_multisite()) {
                    restore_current_blog();
                }
            }
            $this->baseFilesPath = $result;
        }

        return $this->baseFilesPath;
    }

    /**
     * Prepare ready-to-use configs
     * @param array $rawConfigs
     * @return array
     */
    public function handleRawConfigs(array $rawConfigs): array
    {
        return [
            // LAPI connection
            'api_key' => $this->escape((string)$rawConfigs['crowdsec_api_key'] ?? ''),
            'auth_type' => (string)($this->handleRawConfig(
                $rawConfigs,
                'crowdsec_auth_type',
                Constants::AUTH_KEY
            )),
            'tls_cert_path' => (string)($this->handleRawConfig($rawConfigs, 'crowdsec_tls_cert_path', '/')),
            'tls_key_path' => (string)($this->handleRawConfig($rawConfigs, 'crowdsec_tls_key_path', '/')),
            'tls_verify_peer' => (bool)($this->handleRawConfig($rawConfigs, 'crowdsec_tls_verify_peer', false)),
            'tls_ca_cert_path' => (string)($this->handleRawConfig($rawConfigs, 'crowdsec_tls_ca_cert_path', '/')),
            'api_url' => $this->escape((string)$rawConfigs['crowdsec_api_url'] ?? ''),
            'use_curl' => (bool)($this->handleRawConfig($rawConfigs, 'crowdsec_use_curl', false)),
            'api_timeout' => (int)($this->handleRawConfig(
                $rawConfigs,
                'crowdsec_api_timeout',
                Constants::API_TIMEOUT
            )),
            'user_agent_suffix' => 'WordPress' . $this->handleRawConfig($rawConfigs, 'crowdsec_custom_user_agent', ''),
            // Debug
            'debug_mode' => (bool)($this->handleRawConfig($rawConfigs, 'crowdsec_debug_mode', false)),
            'disable_prod_log' => (bool)($this->handleRawConfig($rawConfigs, 'crowdsec_disable_prod_log', false)),
            'log_directory_path' => $this->getBaseFilesPath() . 'logs/',
            'forced_test_ip' => (string)($rawConfigs['crowdsec_forced_test_ip'] ?? ''),
            'forced_test_forwarded_ip' => (string)($rawConfigs['crowdsec_forced_test_forwarded_ip'] ?? ''),
            'display_errors' => (bool)($this->handleRawConfig($rawConfigs, 'crowdsec_display_errors', false)),
            // Bouncer
            'bouncing_level' => (string)($this->handleRawConfig(
                $rawConfigs,
                'crowdsec_bouncing_level',
                Constants::BOUNCING_LEVEL_DISABLED
            )),
            'trust_ip_forward_array' => (array)($rawConfigs['crowdsec_trust_ip_forward_array'] ?? []),
            'fallback_remediation' => (string)($this->handleRawConfig(
                $rawConfigs,
                'crowdsec_fallback_remediation',
                Constants::REMEDIATION_BYPASS
            )),
            // Cache settings
            'stream_mode' => (bool)($this->handleRawConfig($rawConfigs, 'crowdsec_stream_mode', false)),
            'cache_system' => $this->escape((string)($this->handleRawConfig(
                $rawConfigs,
                'crowdsec_cache_system',
                Constants::CACHE_SYSTEM_PHPFS
            ))),
            'fs_cache_path' => $this->getBaseFilesPath() . 'cache/',
            'redis_dsn' => $this->escape((string)$rawConfigs['crowdsec_redis_dsn'] ?? ''),
            'memcached_dsn' => $this->escape((string)$rawConfigs['crowdsec_memcached_dsn'] ?? ''),
            'clean_ip_cache_duration' => (int)($this->handleRawConfig(
                $rawConfigs,
                'crowdsec_clean_ip_cache_duration',
                Constants::CACHE_EXPIRATION_FOR_CLEAN_IP
            )),
            'bad_ip_cache_duration' => (int)($this->handleRawConfig(
                $rawConfigs,
                'crowdsec_bad_ip_cache_duration',
                Constants::CACHE_EXPIRATION_FOR_BAD_IP
            )),
            'captcha_cache_duration' => (int)($this->handleRawConfig(
                $rawConfigs,
                'crowdsec_captcha_cache_duration',
                Constants::CACHE_EXPIRATION_FOR_CAPTCHA
            )),
            // Geolocation
            'geolocation' => [
                'enabled' => (bool)($this->handleRawConfig($rawConfigs, 'crowdsec_geolocation_enabled', false)),
                'type' => (string)($this->handleRawConfig(
                    $rawConfigs,
                    'crowdsec_geolocation_type',
                    Constants::GEOLOCATION_TYPE_MAXMIND
                )),
                'cache_duration' => (int)($this->handleRawConfig(
                    $rawConfigs,
                    'crowdsec_geolocation_cache_duration',
                    Constants::CACHE_EXPIRATION_FOR_GEO
                )),
                'maxmind' => [
                    'database_type' => (string)($this->handleRawConfig(
                        $rawConfigs,
                        'crowdsec_geolocation_maxmind_database_type',
                        Constants::MAXMIND_COUNTRY)
                    ),
                    'database_path' => (string)($this->handleRawConfig(
                        $rawConfigs, 'crowdsec_geolocation_maxmind_database_path', '/')
                    ),
                ]
            ],
            // Ban and Captcha walls
            'hide_mentions' => (bool)($this->handleRawConfig($rawConfigs, 'crowdsec_hide_mentions', false)),
            'custom_css' => $this->specialcharsDecodeEntQuotes(
                (string)($rawConfigs['crowdsec_theme_custom_css'] ?? '')
            ),
            'color' => [
                'text' => [
                    'primary' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_color_text_primary'] ?? '')
                    ),
                    'secondary' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_color_text_secondary'] ?? '')
                    ),
                    'button' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_color_text_button'] ?? '')
                    ),
                    'error_message' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_color_text_error_message'] ?? '')
                    ),
                ],
                'background' => [
                    'page' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_color_background_page'] ?? '')
                    ),
                    'container' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_color_background_container'] ?? '')
                    ),
                    'button' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_color_background_button'] ?? '')
                    ),
                    'button_hover' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_color_background_button_hover'] ?? '')
                    ),
                ],
            ],
            'text' => [
                'ban_wall' => [
                    'tab_title' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_text_ban_wall_tab_title'] ?? '')
                    ),
                    'title' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_text_ban_wall_title'] ?? '')
                    ),
                    'subtitle' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_text_ban_wall_subtitle'] ?? '')
                    ),
                    'footer' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_text_ban_wall_footer'] ?? '')
                    ),
                ],
                'captcha_wall' => [
                    'tab_title' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_text_captcha_wall_tab_title'] ?? '')
                    ),
                    'title' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_text_captcha_wall_title'] ?? '')
                    ),
                    'subtitle' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_text_captcha_wall_subtitle'] ?? '')
                    ),
                    'refresh_image_link' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_text_captcha_wall_refresh_image_link'] ?? '')
                    ),
                    'captcha_placeholder' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_text_captcha_wall_captcha_placeholder'] ?? '')
                    ),
                    'send_button' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_text_captcha_wall_send_button'] ?? '')
                    ),
                    'error_message' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_text_captcha_wall_error_message'] ?? '')
                    ),
                    'footer' => $this->specialcharsDecodeEntQuotes(
                        (string)($rawConfigs['crowdsec_theme_text_captcha_wall_footer'] ?? '')
                    ),
                ],
            ],

        ];
    }

    private function handleRawConfig(array $rawConfigs, string $key, $defaultValue)
    {
        if (!empty($rawConfigs[$key])) {
            return $rawConfigs[$key];
        }

        return $defaultValue;
    }

    /**
     * @return string Ex: "X-Forwarded-For"
     */
    public function getHttpRequestHeader(string $name): ?string
    {
        $headerName = 'HTTP_' . str_replace('-', '_', strtoupper($name));
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
     * The current URI.
     */
    public function getRequestUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? "";
    }

    /**
     * @return string The current IP, even if it's the IP of a proxy
     */
    public function getHttpMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
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
        $bouncingDisabled = (Constants::BOUNCING_LEVEL_DISABLED === $this->getConfig('bouncing_level'));
        if ($bouncingDisabled) {
            $this->logger->warning('Will not bounce', [
                'type' => 'WP_CONFIG_BOUNCER_DISABLED',
                'message' => 'Will not bounce because bouncing is disabled.',
            ]);

            return false;
        }

        // We should not bounce when headers already sent
        if (headers_sent()) {
            return false;
        }
        // Don't bounce favicon calls.
        if ('/favicon.ico' === $this->getRequestUri()) {
            return false;
        }
        // Don't bounce cli
        if (PHP_SAPI === 'cli') {
            return false;
        }

        // when the "crowdsec_public_website_only" is disabled...
        if ($this->shouldNotBounceWpAdmin) {
            // In standalone context, is_admin() does not work. So we check admin section with another method.
            if (defined('CROWDSEC_STANDALONE_RUNNING_CONTEXT')) {
                // TODO improve the way to detect these pages
                // ...don't bounce back office pages
                if (0 === strpos($_SERVER['PHP_SELF'], '/wp-admin')) {
                    return false;
                }
                // ...don't bounce wp-login and wp-cron pages
                if (0 === strpos($_SERVER['PHP_SELF'], '/wp-login.php')) {
                    return false;
                }
                if (0 === strpos($_SERVER['PHP_SELF'], '/wp-cron.php')) {
                    return false;
                }
            } else {
                // ...don't bounce back office pages
                if (is_admin()) {
                    return false;
                }
                // ...don't bounce wp-login and wp-cron pages
                if (in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-cron.php'])) {
                    return false;
                }
            }
        }

        return true;
    }
}
