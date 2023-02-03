<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient;

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
     * @var string The decisions endpoint
     */
    public const DECISIONS_FILTER_ENDPOINT = '/v1/decisions';
    /**
     * @var string The decisions stream endpoint
     */
    public const DECISIONS_STREAM_ENDPOINT = '/v1/decisions/stream';
    /**
     * @var string The Default URL of the CrowdSec LAPI
     */
    public const DEFAULT_LAPI_URL = 'http://localhost:8080';
    /**
     * @var string The user agent prefix used to send request to LAPI
     */
    public const USER_AGENT_PREFIX = 'csphplapi';
    /**
     * @var string The current version of this library
     */
    public const VERSION = 'v2.0.0';
}
