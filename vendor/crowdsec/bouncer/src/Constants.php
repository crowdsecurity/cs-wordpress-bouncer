<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

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
    /** @var string The Default URL of the CrowdSec LAPI */
    public const DEFAULT_LAPI_URL = 'http://localhost:8080';

    /** @var string The last version of this library */
    public const VERSION = 'v0.24.0';

    /** @var string The user agent used to send request to LAPI */
    public const BASE_USER_AGENT = 'PHP CrowdSec Bouncer/' . self::VERSION;

    /** @var int The timeout when calling LAPI */
    public const API_TIMEOUT = 1;

    /** @var int The duration we keep a clean IP in cache */
    public const CACHE_EXPIRATION_FOR_CLEAN_IP = 5;

    /** @var int The duration we keep a bad IP in cache */
    public const CACHE_EXPIRATION_FOR_BAD_IP = 20;

    /** @var int The duration we keep a captcha flow in cache */
    public const CACHE_EXPIRATION_FOR_CAPTCHA = 86400;

    /** @var int The duration we keep a geolocation result in cache */
    public const CACHE_EXPIRATION_FOR_GEO = 86400;

    /** @var string The ban remediation */
    public const REMEDIATION_BAN = 'ban';

    /** @var string The captcha remediation */
    public const REMEDIATION_CAPTCHA = 'captcha';

    /** @var string The bypass remediation */
    public const REMEDIATION_BYPASS = 'bypass';

    /** @var array<string> The list of each known remediation, sorted by priority */
    public const ORDERED_REMEDIATIONS = [self::REMEDIATION_BAN, self::REMEDIATION_CAPTCHA, self::REMEDIATION_BYPASS];

    /** @var string The "disabled" bouncing level */
    public const BOUNCING_LEVEL_DISABLED = 'bouncing_disabled';

    /** @var string The "flex" bouncing level */
    public const BOUNCING_LEVEL_FLEX = 'flex_bouncing';

    /** @var string The "normal" bouncing level */
    public const BOUNCING_LEVEL_NORMAL = 'normal_bouncing';

    /** @var string The "PHPFS" cache system */
    public const CACHE_SYSTEM_PHPFS = 'phpfs';

    /** @var string The "REDIS" cache system */
    public const CACHE_SYSTEM_REDIS = 'redis';

    /** @var string The "MEMCACHED" cache system */
    public const CACHE_SYSTEM_MEMCACHED = 'memcached';

    /** @var string Cache tag for remediation */
    public const CACHE_TAG_REM = 'remediation';

    /** @var string Cache tag for captcha flow */
    public const CACHE_TAG_CAPTCHA = 'captcha';

    /** @var string Cache tag for geolocation */
    public const CACHE_TAG_GEO = 'geolocation';

    /** @var string The "MaxMind" geolocation type */
    public const GEOLOCATION_TYPE_MAXMIND = 'maxmind';

    /** @var string The CrowdSec country scope for decisions */
    public const SCOPE_COUNTRY = 'Country';

    /** @var string The CrowdSec Ip scope for decisions */
    public const SCOPE_IP = 'Ip';

    /** @var string The CrowdSec Range scope for decisions */
    public const SCOPE_RANGE = 'Range';

    /** @var string The Maxmind "Country" database type */
    public const MAXMIND_COUNTRY = 'country';

    /** @var string The Maxmind "City" database type */
    public const MAXMIND_CITY = 'city';

    /** @var string The "disabled" x-forwarded-for setting */
    public const X_FORWARDED_DISABLED = 'no_forward';
}
