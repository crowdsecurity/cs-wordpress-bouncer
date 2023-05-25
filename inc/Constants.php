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

    public const LOG_BASE_PATH = __DIR__ . '/../logs/';
    public const CACHE_PATH = __DIR__ . '/../.cache';
    public const CONFIG_PATH = __DIR__ . '/standalone-settings.php';
    public const VERSION = 'v2.5.0';
}
