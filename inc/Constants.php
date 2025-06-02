<?php

declare(strict_types=1);

namespace CrowdSecWordPressBouncer;

use CrowdSecBouncer\Constants as LibConstants;

/**
 * All plugin constants are defined here.
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
    public const BOUNCER_NAME = 'wordpress-bouncer';
    public const DEFAULT_BASE_FILE_PATH = __DIR__ . '/../../../../wp-content/uploads/crowdsec/';
    public const STANDALONE_CONFIG_PATH = __DIR__ . '/standalone-settings.php';
    public const VERSION = 'v2.11.0';
}
