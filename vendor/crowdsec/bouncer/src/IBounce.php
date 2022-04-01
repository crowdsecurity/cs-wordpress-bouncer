<?php

namespace CrowdSecBouncer;

/**
 * The interface to implement when bouncing.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2021+ CrowdSec
 * @license   MIT License
 */
interface IBounce
{
    /**
     * Init the bouncer.
     */
    public function init(array $configs, array $forcedConfigs = []): Bouncer;

    /**
     * Init the logger.
     */
    public function initLogger(): void;

    /**
     * Get the bouncer instance.
     */
    public function getBouncerInstance(array $settings, bool $forceReload = false): Bouncer;

    /**
     * If there is any technical problem while bouncing, don't block the user. Bypass bouncing and log the error.
     */
    public function safelyBounce(array $configs): bool;

    /**
     * @return string Ex: "X-Forwarded-For"
     */
    public function getHttpRequestHeader(string $name): ?string;

    /**
     * @return string The current IP, even if it's the IP of a proxy
     */
    public function getRemoteIp(): string;

    /**
     * @return string The current IP, even if it's the IP of a proxy
     */
    public function getHttpMethod(): string;

    /**
     * @return array ['hide_crowdsec_mentions': bool, color:[text:['primary' : string, 'secondary' : string, 'button' : string, 'error_message : string' ...]]] (returns an array of option required to build the captcha wall template)
     */
    public function getCaptchaWallOptions(): array;

    /**
     * @return array ['hide_crowdsec_mentions': bool, color:[text:['primary' : string, 'secondary' : string, 'error_message : string' ...]]] (returns an array of option required to build the ban wall template)
     */
    public function getBanWallOptions(): array;

    /**
     * @return array [[string, string], ...] Returns IP ranges to trust as proxies as an array of comparables ip bounds
     */
    public function getTrustForwardedIpBoundsList(): array;

    /**
     * Return a session variable, null if not set.
     */
    public function getSessionVariable(string $name);

    /**
     * Set a session variable.
     */
    public function setSessionVariable(string $name, $value): void;

    /**
     * Unset a session variable, throw an error if this does not exist.
     *
     * @return void;
     */
    public function unsetSessionVariable(string $name): void;

    /**
     * Get the value of a posted field.
     */
    public function getPostedVariable(string $name): ?string;

    /**
     * If the current IP should be bounced or not, matching custom business rules.
     */
    public function shouldBounceCurrentIp(): bool;

    /**
     * Send HTTP response.
     */
    public function sendResponse(?string $body, int $statusCode = 200): void;
}
