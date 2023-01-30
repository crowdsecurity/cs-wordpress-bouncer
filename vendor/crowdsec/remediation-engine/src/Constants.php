<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine;

/**
 * Every constant of the library are set here.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Constants
{
    /** @var int The default duration we keep a bad IP in cache */
    public const CACHE_EXPIRATION_FOR_BAD_IP = 120;
    /** @var int The default duration we keep a clean IP in cache */
    public const CACHE_EXPIRATION_FOR_CLEAN_IP = 60;
    /** @var int The duration we keep a geolocation country result in cache */
    public const CACHE_EXPIRATION_FOR_GEO = 86400;
    /** @var string The "MaxMind" geolocation type */
    public const GEOLOCATION_TYPE_MAXMIND = 'maxmind';
    /** @var string The Maxmind "City" database type */
    public const MAXMIND_CITY = 'city';
    /** @var string The Maxmind "Country" database type */
    public const MAXMIND_COUNTRY = 'country';
    /** @var string The ban remediation */
    public const REMEDIATION_BAN = 'ban';
    /** @var string The bypass remediation */
    public const REMEDIATION_BYPASS = 'bypass';
    /** @var string The bypass remediation */
    public const REMEDIATION_CAPTCHA = 'captcha';
    /** @var string The CrowdSec Country scope for decisions */
    public const SCOPE_COUNTRY = 'country';
    /** @var string The CrowdSec Ip scope for decisions */
    public const SCOPE_IP = 'ip';
    /** @var string The CrowdSec Range scope for decisions */
    public const SCOPE_RANGE = 'range';
    /** @var string The current version of this library */
    public const VERSION = 'v0.6.1';
}
