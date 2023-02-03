<?php

declare(strict_types=1);

namespace CrowdSec\Common\Tests\Unit;

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

use CrowdSec\Common\Client\RequestHandler\Curl;
use CrowdSec\Common\Client\RequestHandler\FileGetContents;
use CrowdSec\Common\Constants;
use CrowdSec\Common\Tests\Constants as TestConstants;
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
        return $this->getMockBuilder(Curl::class)
            ->onlyMethods($methods)
            ->getMock();
    }

    protected function getFGCMock(array $methods = [])
    {
        return $this->getMockBuilder(FileGetContents::class)
            ->onlyMethods($methods)
            ->getMock();
    }
}
