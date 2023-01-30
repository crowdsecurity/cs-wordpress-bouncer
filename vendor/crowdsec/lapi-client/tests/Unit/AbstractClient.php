<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient\Tests\Unit;

/**
 * Abstract class for client test.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\LapiClient\Constants;
use CrowdSec\LapiClient\Tests\Constants as TestConstants;
use PHPUnit\Framework\TestCase;

abstract class AbstractClient extends TestCase
{
    protected $configs = [
        'user_agent_suffix' => TestConstants::USER_AGENT_SUFFIX,
        'user_agent_version' => TestConstants::USER_AGENT_VERSION,
        'auth_type' => Constants::AUTH_KEY,
        'api_key' => TestConstants::API_KEY,
        'api_timeout' => TestConstants::API_TIMEOUT,
    ];

    protected $tlsConfigs = [
        'user_agent_suffix' => TestConstants::USER_AGENT_SUFFIX,
        'auth_type' => Constants::AUTH_TLS,
        'api_timeout' => TestConstants::API_TIMEOUT,
        'tls_cert_path' => 'tls_cert_path_test',
        'tls_key_path' => 'tls_key_path_test',
        'tls_verify_peer' => true,
        'tls_ca_cert_path' => 'tls_ca_cert_path_test',
    ];

    protected function getCurlMock(array $methods = [])
    {
        $methods = array_merge(['exec', 'getResponseHttpCode'], $methods);

        return $this->getMockBuilder('CrowdSec\LapiClient\RequestHandler\Curl')
            ->onlyMethods($methods)
            ->getMock();
    }

    protected function getFGCMock()
    {
        return $this->getMockBuilder('CrowdSec\LapiClient\RequestHandler\FileGetContents')
            ->onlyMethods(['exec'])
            ->getMock();
    }
}
