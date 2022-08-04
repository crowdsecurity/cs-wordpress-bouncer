<?php

namespace CrowdSecBouncer;

use ErrorException;
use Exception;
use IPLib\Factory;
use Symfony\Component\Cache\Exception\CacheException;

/**
 * The class that apply a bounce in standalone mode.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2021+ CrowdSec
 * @license   MIT License
 */
class StandaloneBounce extends AbstractBounce
{
    /**
     * Initialize the bouncer.
     *
     * @throws CacheException
     * @throws ErrorException
     * @throws \Psr\Cache\InvalidArgumentException|BouncerException
     */
    public function init(array $configs): Bouncer
    {
        // Convert array of string to array of array with comparable IPs
        if (\is_array(($configs['trust_ip_forward_array']))) {
            $forwardConfigs = $configs['trust_ip_forward_array'];
            $finalForwardConfigs = [];
            foreach ($forwardConfigs as $forwardConfig) {
                if (\is_string($forwardConfig)) {
                    $parsedString = Factory::parseAddressString($forwardConfig, 3);
                    if (!empty($parsedString)) {
                        $comparableValue = $parsedString->getComparableString();
                        $finalForwardConfigs[] = [$comparableValue, $comparableValue];
                    }
                } elseif (\is_array($forwardConfig)) {
                    $finalForwardConfigs[] = $forwardConfig;
                }
            }
            $configs['trust_ip_forward_array'] = $finalForwardConfigs;
        }
        $this->settings = $configs;

        return $this->getBouncerInstance($this->settings);
    }

    /**
     * Get the bouncer instance.
     *
     * @throws CacheException
     * @throws ErrorException
     * @throws \Psr\Cache\InvalidArgumentException|BouncerException
     */
    public function getBouncerInstance(array $settings): Bouncer
    {
        $this->settings = array_merge($this->settings, $settings);
        $apiUserAgent = 'Standalone CrowdSec PHP Bouncer/' . Constants::VERSION;

        $this->settings['api_user_agent'] = $apiUserAgent;
        $bouncerConfigs = $this->prepareBouncerConfigs();

        // Instantiate bouncer
        $this->bouncer = new Bouncer($bouncerConfigs, $this->logger);

        return $this->bouncer;
    }

    public function initLogger(array $configs): void
    {
        $this->initLoggerHelper($configs, 'php_standalone_bouncer');
    }

    /**
     * @param string $name Ex: "X-Forwarded-For"
     */
    public function getHttpRequestHeader(string $name): ?string
    {
        $headerName = 'HTTP_' . str_replace('-', '_', strtoupper($name));
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
     * The current HTTP method.
     */
    public function getHttpMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return array ['hide_crowdsec_mentions': bool, color:[text:['primary' : string, 'secondary' : string, 'button' :
     *     string, 'error_message : string' ...]]] (returns an array of option required to build the captcha wall
     *     template)
     */
    public function getCaptchaWallOptions(): array
    {
        return [
            'hide_crowdsec_mentions' => $this->getBoolSettings('hide_mentions'),
            'color' => [
                'text' => [
                    'primary' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_text_primary'),
                        \ENT_QUOTES
                    ),
                    'secondary' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_text_secondary'),
                        \ENT_QUOTES
                    ),
                    'button' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_text_button'),
                        \ENT_QUOTES
                    ),
                    'error_message' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_text_error_message'),
                        \ENT_QUOTES
                    ),
                ],
                'background' => [
                    'page' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_background_page'),
                        \ENT_QUOTES
                    ),
                    'container' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_background_container'),
                        \ENT_QUOTES
                    ),
                    'button' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_background_button'),
                        \ENT_QUOTES
                    ),
                    'button_hover' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_background_button_hover'),
                        \ENT_QUOTES
                    ),
                ],
            ],
            'text' => [
                'captcha_wall' => [
                    'tab_title' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_text_captcha_wall_tab_title'),
                        \ENT_QUOTES
                    ),
                    'title' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_text_captcha_wall_title'),
                        \ENT_QUOTES
                    ),
                    'subtitle' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_text_captcha_wall_subtitle'),
                        \ENT_QUOTES
                    ),
                    'refresh_image_link' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_text_captcha_wall_refresh_image_link'),
                        \ENT_QUOTES
                    ),
                    'captcha_placeholder' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_text_captcha_wall_captcha_placeholder'),
                        \ENT_QUOTES
                    ),
                    'send_button' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_text_captcha_wall_send_button'),
                        \ENT_QUOTES
                    ),
                    'error_message' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_text_captcha_wall_error_message'),
                        \ENT_QUOTES
                    ),
                    'footer' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_text_captcha_wall_footer'),
                        \ENT_QUOTES
                    ),
                ],
            ],
            'custom_css' => $this->getStringSettings('theme_custom_css'),
        ];
    }

    /**
     * @return array ['hide_crowdsec_mentions': bool, color:[text:['primary' : string, 'secondary' : string,
     *     'error_message : string' ...]]] (returns an array of option required to build the ban wall template)
     */
    public function getBanWallOptions(): array
    {
        return [
            'hide_crowdsec_mentions' => $this->getBoolSettings('hide_mentions'),
            'color' => [
                'text' => [
                    'primary' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_text_primary'),
                        \ENT_QUOTES
                    ),
                    'secondary' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_text_secondary'),
                        \ENT_QUOTES
                    ),
                    'error_message' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_text_error_message'),
                        \ENT_QUOTES
                    ),
                ],
                'background' => [
                    'page' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_background_page'),
                        \ENT_QUOTES
                    ),
                    'container' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_background_container'),
                        \ENT_QUOTES
                    ),
                    'button' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_background_button'),
                        \ENT_QUOTES
                    ),
                    'button_hover' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_color_background_button_hover'),
                        \ENT_QUOTES
                    ),
                ],
            ],
            'text' => [
                'ban_wall' => [
                    'tab_title' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_text_ban_wall_tab_title'),
                        \ENT_QUOTES
                    ),
                    'title' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_text_ban_wall_title'),
                        \ENT_QUOTES
                    ),
                    'subtitle' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_text_ban_wall_subtitle'),
                        \ENT_QUOTES
                    ),
                    'footer' => htmlspecialchars_decode(
                        $this->getStringSettings('theme_text_ban_wall_footer'),
                        \ENT_QUOTES
                    ),
                ],
            ],
            'custom_css' => htmlspecialchars_decode($this->getStringSettings('theme_custom_css'), \ENT_QUOTES),
        ];
    }

    /**
     * @return array [[string, string], ...] Returns IP ranges to trust as proxies as an array of comparables ip bounds
     */
    public function getTrustForwardedIpBoundsList(): array
    {
        return $this->getArraySettings('trust_ip_forward_array');
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
        $excludedURIs = $this->getArraySettings('excluded_uris');
        if (\in_array($_SERVER['REQUEST_URI'], $excludedURIs)) {
            $this->logger->debug('', [
                'type' => 'SHOULD_NOT_BOUNCE',
                'message' => 'This URI is excluded from bouncing: ' . $_SERVER['REQUEST_URI'],
            ]);

            return false;
        }
        $bouncingDisabled = (Constants::BOUNCING_LEVEL_DISABLED === $this->getStringSettings('bouncing_level'));
        if ($bouncingDisabled) {
            $this->logger->debug('', [
                'type' => 'SHOULD_NOT_BOUNCE',
                'message' => Constants::BOUNCING_LEVEL_DISABLED,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Send HTTP response.
     * @throws BouncerException
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
        exit();
    }

    /**
     * If there is any technical problem while bouncing, don't block the user. Bypass bouncing and log the error.
     *
     * @param array $configs
     * @return bool
     * @throws BouncerException
     * @throws CacheException
     * @throws ErrorException
     * @throws \Psr\Cache\CacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function safelyBounce(array $configs): bool
    {
        $result = false;
        set_error_handler(function ($errno, $errstr) {
            throw new BouncerException("$errstr (Error level: $errno)");
        });
        try {
            $this->settings = $configs;
            $this->initLogger($configs);
            if ($this->shouldBounceCurrentIp()) {
                $this->init($configs);
                $this->run();
                $result = true;
            }
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('', [
                    'type' => 'EXCEPTION_WHILE_BOUNCING',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
            if (!empty($configs['display_errors'])) {
                throw $e;
            }
        }
        restore_error_handler();

        return $result;
    }
}
