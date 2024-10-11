<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient\Tests;

/**
 * Every constant for testing.
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
    /**
     * @var string The user agent suffix used to send request to LAPI
     */
    public const USER_AGENT_SUFFIX = 'PHPLAPITEST';

    /**
     * @var string The user agent version used to send request to LAPI
     */
    public const USER_AGENT_VERSION = 'v0.0.0';

    /**
     * @var string The user agent suffix used to send request to LAPI
     */
    public const API_KEY = '1234abcd';

    /**
     * @var string The timeout used to request LAPI
     */
    public const API_TIMEOUT = 25;

    public const API_CONNECT_TIMEOUT = 13;

    public const APPSEC_TIMEOUT_MS = 700;

    public const APPSEC_CONNECT_TIMEOUT_MS = 300;

    public const BAD_IP = '1.2.3.4';
    public const BAD_IP_APPSEC = '1.2.3.5';
    public const IP_RANGE = '24';
    public const JAPAN = 'JP';
}
