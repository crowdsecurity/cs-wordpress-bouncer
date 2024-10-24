<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine;

use CrowdSec\Common\Constants as CommonConstants;

/**
 * Main constants of the library are set here.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Constants extends CommonConstants
{
    /**
     * @var string The AppSec action name to allow the request
     */
    public const APPSEC_ACTION_ALLOW = 'allow';
    /**
     * @var string The AppSec action name to block the request
     */
    public const APPSEC_ACTION_BLOCK = 'block';
    /**
     * @var string The AppSec action name to send only the headers
     */
    public const APPSEC_ACTION_HEADERS_ONLY = 'headers_only';
    /**
     * @var int The default maximum body size for AppSec requests (in KB)
     */
    public const APPSEC_DEFAULT_MAX_BODY_SIZE = 1024;
    /**
     * @var int The default duration we keep a bad IP in cache (in seconds)
     */
    public const CACHE_EXPIRATION_FOR_BAD_IP = 120;
    /**
     * @var int The default duration we keep a clean IP in cache (in seconds)
     */
    public const CACHE_EXPIRATION_FOR_CLEAN_IP = 60;
    /**
     * @var int The duration we keep a geolocation country result in cache
     */
    public const CACHE_EXPIRATION_FOR_GEO = 86400;
    /**
     * @var string The "MaxMind" geolocation type
     */
    public const GEOLOCATION_TYPE_MAXMIND = 'maxmind';
    /**
     * @var string The Maxmind "City" database type
     */
    public const MAXMIND_CITY = 'city';
    /**
     * @var string The Maxmind "Country" database type
     */
    public const MAXMIND_COUNTRY = 'country';
    /**
     * @var int The default refresh frequency (in seconds)
     */
    public const REFRESH_FREQUENCY = 14400;
    /**
     * @var string The current version of this library
     */
    public const VERSION = 'v3.5.0';
}
