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
use CrowdSec\LapiClient\TimeoutException;

/**
 * @uses \CrowdSec\LapiClient\Configuration::getConfigTreeBuilder
 * @uses \CrowdSec\LapiClient\Bouncer::__construct
 * @uses \CrowdSec\LapiClient\Bouncer::configure
 * @uses \CrowdSec\LapiClient\Bouncer::formatUserAgent
 * @uses \CrowdSec\LapiClient\Bouncer::manageRequest
 * @uses \CrowdSec\LapiClient\Configuration::addConnectionNodes
 * @uses \CrowdSec\LapiClient\Configuration::validate
 * @uses \CrowdSec\LapiClient\Configuration::addAppSecNodes
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

    public function testFilteredDecisionsWithTimeout()
    {
        // Timeout test
        $mockFGCRequest = $this->getFGCMock(['exec']);
        $mockFGCRequest->method('exec')
            ->willReturnCallback(function () {
                // Trigger a warning that will be caught by the method's error handler
                trigger_error('it appears that request timed out', \E_USER_ERROR);

                // Simulate a failure response
                return ['response' => false];
            });

        $client = new Bouncer($this->configs, $mockFGCRequest);
        $error = false;
        $message = '';
        try {
            $client->getFilteredDecisions();
        } catch (TimeoutException $e) {
            $error = true;
            $message = $e->getMessage();
        }
        $this->assertEquals(
            true,
            $error,
            'A timeout should be thrown'
        );

        $this->assertEquals(
            'file_get_contents call timeout: it appears that request timed out',
            $message,
            'A timeout should be thrown'
        );
    }
}
