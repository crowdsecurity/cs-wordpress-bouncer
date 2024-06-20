<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

use CrowdSec\RemediationEngine\Constants as RemConstants;

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
class Constants extends RemConstants
{
    /** @var string The "disabled" bouncing level */
    public const BOUNCING_LEVEL_DISABLED = 'bouncing_disabled';
    /** @var string The "flex" bouncing level */
    public const BOUNCING_LEVEL_FLEX = 'flex_bouncing';
    /** @var string The "normal" bouncing level */
    public const BOUNCING_LEVEL_NORMAL = 'normal_bouncing';
    /** @var int The duration we keep a captcha flow in cache */
    public const CACHE_EXPIRATION_FOR_CAPTCHA = 86400;
    /** @var string The "MEMCACHED" cache system */
    public const CACHE_SYSTEM_MEMCACHED = 'memcached';
    /** @var string The "PHPFS" cache system */
    public const CACHE_SYSTEM_PHPFS = 'phpfs';
    /** @var string The "REDIS" cache system */
    public const CACHE_SYSTEM_REDIS = 'redis';
    /** @var string Cache tag for captcha flow */
    public const CACHE_TAG_CAPTCHA = 'captcha';
    /** @var string The Default URL of the CrowdSec LAPI */
    public const DEFAULT_LAPI_URL = 'http://localhost:8080';
    /** @var string Path for html templates folder (e.g. ban and captcha wall) */
    public const TEMPLATES_DIR = __DIR__ . '/templates';
    /** @var string The last version of this library */
    public const VERSION = 'v2.2.0';
    /** @var string The "disabled" x-forwarded-for setting */
    public const X_FORWARDED_DISABLED = 'no_forward';
}
