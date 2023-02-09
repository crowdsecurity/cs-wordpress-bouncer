<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Tests;

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
     * @var string The user agent suffix used to send request to CAPI
     */
    public const USER_AGENT_SUFFIX = 'PHPCAPITEST';

    /**
     * @var string The user agent version used to send request to CAPI
     */
    public const USER_AGENT_VERSION = 'v0.0.0';

    /**
     * @var string The machine id prefix used to send request to CAPI
     */
    public const MACHINE_ID_PREFIX = 'capiclienttest';

    /**
     * @var array The scenario used to send request to CAPI
     */
    public const SCENARIOS = ['test/scenario'];

    /**
     * @var string The password used to send request to CAPI
     */
    public const PASSWORD = 'test-password';

    /**
     * @var string The token used to send request to CAPI
     */
    public const TOKEN = 'test-token';

    /**
     * @var string The machine_id used to send request to CAPI
     */
    public const MACHINE_ID = 'test-machine-id';

    /**
     * @var string The timeout for CAPI request
     */
    public const API_TIMEOUT = 20;

    /**
     * @var string An IP for test
     */
    public const IP = '1.2.3.4';
}
