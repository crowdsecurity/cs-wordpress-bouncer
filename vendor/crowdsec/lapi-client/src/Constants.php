<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient;

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
class Constants
{
    /** @var int The default timeout (in seconds) when calling LAPI */
    public const API_TIMEOUT = 120;
    /** @var string The API-KEY auth type */
    public const AUTH_KEY = 'api_key';
    /** @var string The TLS auth type */
    public const AUTH_TLS = 'tls';
    /** @var string The Default URL of the CrowdSec LAPI */
    public const DEFAULT_LAPI_URL = 'http://localhost:8080';
    /** @var string The CrowdSec country scope for decisions */
    public const SCOPE_COUNTRY = 'country';
    /** @var string The CrowdSec ip scope for decisions */
    public const SCOPE_IP = 'ip';
    /** @var string The CrowdSec range scope for decisions */
    public const SCOPE_RANGE = 'range';
    /**
     * @var string The user agent prefix used to send request to LAPI
     */
    public const USER_AGENT_PREFIX = 'csphplapi';
    /**
     * @var string The current version of this library
     */
    public const VERSION = 'v0.4.0';
}
