<?php

declare(strict_types=1);

namespace CrowdSec\Common\Tests;

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
     * @var string The user agent suffix used to send request to CrowdSec
     */
    public const USER_AGENT_SUFFIX = 'PHPCOMMONTEST';

    /**
     * @var string The user agent version used to send request to CrowdSec
     */
    public const USER_AGENT_VERSION = 'v0.0.0';

    /**
     * @var string The user agent suffix used to send request to CrowdSec
     */
    public const API_KEY = '1234abcd';

    /**
     * @var string The timeout used to request CrowdSec
     */
    public const API_TIMEOUT = 25;

    public const API_URL = 'http://unit.crowdsec.net';
}
