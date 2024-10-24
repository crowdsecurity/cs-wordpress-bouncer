<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient\Tests\Unit;

/**
 * Test for client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\Common\Client\ClientException;
use CrowdSec\Common\Client\HttpMessage\Response;
use CrowdSec\LapiClient\Bouncer;
use CrowdSec\LapiClient\Constants;
use CrowdSec\LapiClient\Tests\MockedData;
use CrowdSec\LapiClient\Tests\PHPUnitUtil;

/**
 * @uses \CrowdSec\LapiClient\Configuration::getConfigTreeBuilder
 * @uses \CrowdSec\LapiClient\Bouncer::formatUserAgent
 * @uses \CrowdSec\LapiClient\Configuration::addConnectionNodes
 * @uses \CrowdSec\LapiClient\Configuration::validate
 * @uses \CrowdSec\LapiClient\Configuration::addAppSecNodes
 *
 * @covers \CrowdSec\LapiClient\Bouncer::__construct
 * @covers \CrowdSec\LapiClient\Bouncer::configure
 * @covers \CrowdSec\LapiClient\Bouncer::cleanHeadersForLog
 * @covers \CrowdSec\LapiClient\Bouncer::cleanRawBodyForLog
 */
final class AbstractClientTest extends AbstractClient
{
    public function testClientInit()
    {
        $client = new Bouncer($this->configs);

        $url = $client->getUrl();
        $this->assertEquals(
            Constants::DEFAULT_LAPI_URL . '/',
            $url,
            'Url should be default'
        );
        $this->assertEquals(
            '/',
            substr($url, -1),
            'Url should end with /'
        );

        $requestHandler = $client->getRequestHandler();
        $this->assertEquals(
            'CrowdSec\Common\Client\RequestHandler\Curl',
            get_class($requestHandler),
            'Request handler must be curl by default'
        );

        $client = new Bouncer(array_merge($this->configs, ['api_url' => 'http://test']));
        $url = $client->getUrl();
        $this->assertEquals(
            'http://test/',
            $url,
            'Url should be ok if specified'
        );
        $this->assertEquals(
            '/',
            substr($url, -1),
            'Url should end with /'
        );

        $error = false;
        try {
            new Bouncer($this->configs, new \DateTime());
        } catch (\TypeError $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/must .*RequestHandler/',
            $error,
            'Bad request handler should throw an error'
        );
    }

    public function testPrivateOrProtectedMethods()
    {
        // cleanHeadersForLog
        $client = new Bouncer($this->configs);
        $headers = ['test' => 'test'];
        $cleanedHeaders = PHPUnitUtil::callMethod(
            $client,
            'cleanHeadersForLog',
            [$headers]
        );
        $this->assertEquals(
            $headers,
            $cleanedHeaders,
            'Headers should be untouched as they are not sensitive'
        );

        $headers = ['test' => 'test', 'X-Crowdsec-Appsec-Api-Key' => '28'];
        $cleanedHeaders = PHPUnitUtil::callMethod(
            $client,
            'cleanHeadersForLog',
            [$headers]
        );
        $this->assertEquals(
            ['test' => 'test', 'X-Crowdsec-Appsec-Api-Key' => '***'],
            $cleanedHeaders,
            'Headers should be cleaned as they are not sensitive'
        );
        // cleanRawBodyForLog
        $rawBody = 'test';
        $cleanedRawBody = PHPUnitUtil::callMethod(
            $client,
            'cleanRawBodyForLog',
            [$rawBody, 10]
        );
        $this->assertEquals(
            $rawBody,
            $cleanedRawBody,
            'Raw body should be untouched if not too long'
        );
        $rawBody = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $cleanedRawBody = PHPUnitUtil::callMethod(
            $client,
            'cleanRawBodyForLog',
            [$rawBody, 10]
        );
        $this->assertEquals(
            'aaaaaaaaaa...[TRUNCATED]',
            $cleanedRawBody,
            'Raw body should be cut if too long'
        );
        $fullUrl = PHPUnitUtil::callMethod(
            $client,
            'getFullUrl',
            ['/test-endpoint']
        );
        $this->assertEquals(
            Constants::DEFAULT_LAPI_URL . '/test-endpoint',
            $fullUrl,
            'Full Url should be ok'
        );

        $jsonBody = json_encode(['message' => 'ok']);

        $response = new Response($jsonBody, 200);

        $formattedResponse = ['message' => 'ok'];

        $validateResponse = PHPUnitUtil::callMethod(
            $client,
            'formatResponseBody',
            [$response]
        );
        $this->assertEquals(
            $formattedResponse,
            $validateResponse,
            'Array response should be valid'
        );

        $jsonBody = '{bad response]]]';
        $response = new Response($jsonBody, 200);
        $error = false;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'formatResponseBody',
                [$response]
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/not a valid json/',
            $error,
            'Bad JSON should be detected'
        );

        $response = new Response(MockedData::DECISIONS_FILTER, 200);

        $decodedResponse = PHPUnitUtil::callMethod(
            $client,
            'formatResponseBody',
            [$response]
        );

        $this->assertEquals(
            json_decode(MockedData::DECISIONS_FILTER, true),
            $decodedResponse,
            'Decoded response should be correct'
        );

        $response = new Response(MockedData::UNAUTHORIZED, 403);

        $error = false;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'formatResponseBody',
                [$response]
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/403.*Unauthorized/',
            $error,
            'Should throw error on 403'
        );

        $response = new Response('', 200);

        $error = false;
        $decoded = [];
        try {
            $decoded = PHPUnitUtil::callMethod(
                $client,
                'formatResponseBody',
                [$response]
            );
        } catch (ClientException $e) {
            $error = true;
        }

        $this->assertEquals(
            false,
            $error,
            'An empty response body should not throw error'
        );

        $this->assertEquals(
            [],
            $decoded,
            'An empty response body should not return some array'
        );

        $response = new Response('', 500);

        $error = false;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'formatResponseBody',
                [$response]
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/500.*/',
            $error,
            'An empty response body should throw error for bad status'
        );

        $error = false;
        try {
            new Response(['test'], 200);
        } catch (\TypeError $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/type .*string/',
            $error,
            'If response body is not a string it should throw error'
        );
    }
}
