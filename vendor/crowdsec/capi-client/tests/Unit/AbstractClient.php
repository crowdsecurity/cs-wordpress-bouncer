<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Tests\Unit;

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

use CrowdSec\CapiClient\Client\CapiHandler\Curl;
use CrowdSec\CapiClient\Client\CapiHandler\FileGetContents;
use CrowdSec\CapiClient\Tests\Constants as TestConstants;
use PHPUnit\Framework\TestCase;

abstract class AbstractClient extends TestCase
{
    protected $configs = [
        'machine_id_prefix' => TestConstants::MACHINE_ID_PREFIX,
        'user_agent_suffix' => TestConstants::USER_AGENT_SUFFIX,
        'user_agent_version' => TestConstants::USER_AGENT_VERSION,
        'scenarios' => TestConstants::SCENARIOS,
        'api_timeout' => TestConstants::API_TIMEOUT,
    ];

    protected function getCurlMock(array $methods = [])
    {
        $methods = array_merge(['exec', 'getResponseHttpCode'], $methods);

        return $this->getMockBuilder(Curl::class)
            ->onlyMethods($methods)
            ->getMock();
    }

    protected function getFileStorageMock()
    {
        return $this->getMockBuilder('CrowdSec\CapiClient\Storage\FileStorage')
            ->onlyMethods(
                [
                    'retrieveToken',
                    'retrievePassword',
                    'retrieveMachineId',
                    'retrieveScenarios',
                    'storePassword',
                    'storeMachineId',
                    'storeScenarios',
                    'storeToken',
                ]
            )
            ->getMock();
    }

    protected function getFGCMock()
    {
        return $this->getMockBuilder(FileGetContents::class)
            ->onlyMethods(['exec'])
            ->getMock();
    }
}
