<?php

declare(strict_types=1);

use CrowdSecBouncer\Constants as LibConstants;

/**
 * Every constant of the plugin are set here.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Constants extends LibConstants
{

    public const CROWDSEC_LOG_BASE_PATH = __DIR__ . '/../logs/';
    public const CROWDSEC_LOG_PATH = __DIR__ . '/../logs/prod.log';
    public const CROWDSEC_DEBUG_LOG_PATH = __DIR__ . '/../logs/debug.log';
    public const CROWDSEC_CACHE_PATH = __DIR__ . '/../.cache';
    public const CROWDSEC_CONFIG_PATH = __DIR__ . '/standalone-settings.php';
    public const CROWDSEC_BOUNCER_USER_AGENT = 'WordPress CrowdSec Bouncer/v1.8.1';
    public const CROWDSEC_BOUNCER_GEOLOCATION_DIR = __DIR__ . '/../geolocation';
    public const CROWDSEC_BOUNCER_TLS_DIR = __DIR__ . '/../tls';

}
