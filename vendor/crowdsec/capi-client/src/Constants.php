<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient;

use CrowdSec\Common\Constants as CommonConstants;

/**
 * Main constants of the library.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Constants extends CommonConstants
{
    /**
     * @var string The decisions stream endpoint
     */
    public const DECISIONS_STREAM_ENDPOINT = '/decisions/stream';
    /**
     * @var string The watchers enroll endpoint
     */
    public const ENROLL_ENDPOINT = '/watchers/enroll';
    /**
     * @var string The development environment flag
     */
    public const ENV_DEV = 'dev';
    /**
     * @var string The production environment flag
     */
    public const ENV_PROD = 'prod';
    /**
     * @var string The watchers login endpoint
     */
    public const LOGIN_ENDPOINT = '/watchers/login';
    /**
     * @var int The number of login retry attempts in case of 401
     */
    public const LOGIN_RETRY = 1;
    /**
     * @var int The machine_id length
     */
    public const MACHINE_ID_LENGTH = 48;
    /**
     * @var string The watchers login endpoint
     */
    public const METRICS_ENDPOINT = '/metrics';
    /**
     * @var int The password length
     */
    public const PASSWORD_LENGTH = 32;
    /**
     * @var string The watchers register endpoint
     */
    public const REGISTER_ENDPOINT = '/watchers';
    /**
     * @var int The number of register retry attempts in case of 500
     */
    public const REGISTER_RETRY = 1;
    /**
     * @var string The signals push endpoint
     */
    public const SIGNALS_ENDPOINT = '/signals';
    /**
     * @var string The signal manual trust
     */
    public const TRUST_MANUAL = 'manual';
    /**
     * @var string The Development URL of the CrowdSec CAPI
     */
    public const URL_DEV = 'https://api.dev.crowdsec.net/v3/';
    /**
     * @var string The Production URL of the CrowdSec CAPI
     */
    public const URL_PROD = 'https://api.crowdsec.net/v3/';
    /**
     * @var string The user agent prefix used to send request to CAPI
     */
    public const USER_AGENT_PREFIX = 'csphpcapi';
    /**
     * @var string The current version of this library
     */
    public const VERSION = 'v3.2.0';
}
