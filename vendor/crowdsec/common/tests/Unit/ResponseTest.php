<?php

declare(strict_types=1);

namespace CrowdSec\Common\Client\Tests\Unit;

/**
 * Test for response.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\Common\Client\HttpMessage\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CrowdSec\Common\Client\HttpMessage\Response::getJsonBody
 * @covers \CrowdSec\Common\Client\HttpMessage\Response::getStatusCode
 * @covers \CrowdSec\Common\Client\HttpMessage\Response::__construct
 * @covers \CrowdSec\Common\Client\HttpMessage\AbstractMessage::getHeaders
 */
final class ResponseTest extends TestCase
{
    public function testConstructor()
    {
        $response = new Response('{}', 200, ['test' => 'test']);

        $headers = $response->getHeaders();
        $jsonBody = $response->getJsonBody();
        $statusCode = $response->getStatusCode();

        $this->assertEquals(
            '{}',
            $jsonBody,
            'Response json body should be set'
        );

        $this->assertEquals(
            200,
            $statusCode,
            'Response status code should be set'
        );

        $this->assertEquals(
            [
                'test' => 'test',
            ],
            $headers,
            'Response headers should be set'
        );
    }
}
