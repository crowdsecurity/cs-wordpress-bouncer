<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace CrowdSec\LapiClient\Tests\Unit;

/**
 * Test for FGC request handler.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\LapiClient\Bouncer;
use CrowdSec\LapiClient\Tests\MockedData;

/**
 * @uses \CrowdSec\LapiClient\Configuration::getConfigTreeBuilder
 * @uses \CrowdSec\LapiClient\Bouncer::__construct
 * @uses \CrowdSec\LapiClient\Bouncer::configure
 * @uses \CrowdSec\LapiClient\Bouncer::formatUserAgent
 * @uses \CrowdSec\LapiClient\Bouncer::manageRequest
 * @uses \CrowdSec\LapiClient\Configuration::addConnectionNodes
 * @uses \CrowdSec\LapiClient\Configuration::validate
 *
 * @covers \CrowdSec\LapiClient\Bouncer::getStreamDecisions
 * @covers \CrowdSec\LapiClient\Bouncer::getFilteredDecisions
 */
final class FileGetContentsTest extends AbstractClient
{
    public function testDecisionsStream()
    {
        // Success test
        $mockFGCRequest = $this->getFGCMock(['exec']);
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                [
                    'response' => MockedData::DECISIONS_STREAM_LIST,
                    'header' => ['HTTP/1.1 ' . MockedData::HTTP_200 . ' OK'],
                ]
            )
        );

        $client = new Bouncer($this->configs, $mockFGCRequest);
        $decisionsResponse = $client->getStreamDecisions(true);

        $this->assertEquals(
            json_decode(MockedData::DECISIONS_STREAM_LIST, true),
            $decisionsResponse,
            'Success get decisions stream'
        );
    }

    public function testFilteredDecisions()
    {
        // Success test
        $mockFGCRequest = $this->getFGCMock(['exec']);
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                [
                    'response' => MockedData::DECISIONS_FILTER,
                    'header' => ['HTTP/1.1 ' . MockedData::HTTP_200 . ' OK'],
                ]
            )
        );

        $client = new Bouncer($this->configs, $mockFGCRequest);
        $decisionsResponse = $client->getFilteredDecisions();

        $this->assertEquals(
            json_decode(MockedData::DECISIONS_FILTER, true),
            $decisionsResponse,
            'Success get decisions stream'
        );
    }
}
