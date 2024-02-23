<?php

declare(strict_types=1);

namespace CrowdSec\Common\Tests\Unit;

/**
 * Test for file storage.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\Common\Client\AbstractClient;
use CrowdSec\Common\Client\ClientException;
use CrowdSec\Common\Client\HttpMessage\Response;
use CrowdSec\Common\Client\RequestHandler\Curl;
use CrowdSec\Common\Client\RequestHandler\FileGetContents;
use CrowdSec\Common\Logger\FileLog;
use CrowdSec\Common\Tests\Constants;
use CrowdSec\Common\Tests\MockedData;
use CrowdSec\Common\Tests\PHPUnitUtil;
use CrowdSec\Common\Tests\Unit\AbstractClient as TestAbstractClient;
use Monolog\Logger;
use PHPUnit\TextUI\XmlConfiguration\File;

/**
 * @covers \CrowdSec\Common\Client\AbstractClient::__construct
 * @covers \CrowdSec\Common\Client\AbstractClient::getConfig
 * @covers \CrowdSec\Common\Client\AbstractClient::getLogger
 * @covers \CrowdSec\Common\Client\AbstractClient::getRequestHandler
 *
 * @uses \CrowdSec\Common\Client\RequestHandler\AbstractRequestHandler::__construct
 *
 * @covers \CrowdSec\Common\Client\RequestHandler\AbstractRequestHandler::getConfig
 * @covers \CrowdSec\Common\Client\AbstractClient::getUrl
 * @covers \CrowdSec\Common\Client\AbstractClient::getFullUrl
 * @covers \CrowdSec\Common\Client\AbstractClient::formatResponseBody
 *
 * @uses \CrowdSec\Common\Client\HttpMessage\Response::__construct
 * @uses \CrowdSec\Common\Client\HttpMessage\Response::getJsonBody
 * @uses \CrowdSec\Common\Client\HttpMessage\Response::getStatusCode
 * @uses \CrowdSec\Common\Logger\FileLog::__construct
 *
 * @covers \CrowdSec\Common\Client\AbstractClient::request
 * @covers \CrowdSec\Common\Client\AbstractClient::sendRequest
 *
 * @uses \CrowdSec\Common\Client\HttpMessage\Request::__construct
 * @uses \CrowdSec\Common\Logger\AbstractLog::__construct
 * @uses \CrowdSec\Common\Logger\FileLog::buildFileHandler
 */
final class AbstractClientTest extends TestAbstractClient
{
    protected $configs = ['api_url' => Constants::API_URL];

    public function testConstruct()
    {
        $configs = array_merge($this->configs, ['api_url' => Constants::API_URL]);
        $client = $this->getMockForAbstractClass(AbstractClient::class, [$configs]);

        $this->assertEquals(
            Curl::class,
            \get_class($client->getRequestHandler()),
            'Client should be cURL by default');
        $this->assertEquals(
            Constants::API_URL,
            $client->getConfig('api_url'),
            'Config should be set');

        $requestHandler = $client->getRequestHandler();

        $this->assertEquals(
            Constants::API_URL,
            $requestHandler->getConfig('api_url'),
            'Config should be pass to the curl request handler');

        $this->assertEquals(
            Logger::class,
            \get_class($client->getLogger()),
            'Logger should be set by default');

        $this->assertEquals(
            Constants::API_URL . '/',
            $client->getUrl(),
            'Url should have a trailing slash');

        $configs = array_merge($this->configs, ['api_url' => Constants::API_URL]);
        $requestHandler = $this->getFGCMock();
        $logger = new FileLog();
        $client = $this->getMockForAbstractClass(AbstractClient::class, [$configs, $requestHandler, $logger]);
        $this->assertInstanceOf(
            FileGetContents::class,
            $client->getRequestHandler(),
            'Client should be file get contents if set');

        $requestHandler = $client->getRequestHandler();

        $this->assertEquals(
            null,
            $requestHandler->getConfig('api_url'),
            'Config should not be pass to the file get contents request handler if not specified');

        $this->assertEquals(
            FileLog::class,
            \get_class($client->getLogger()),
            'Logger should be set');
    }

    public function testPrivateOrProtectedMethods()
    {
        // getFullUrl
        $configs = array_merge($this->configs, ['api_url' => Constants::API_URL]);
        $client = $this->getMockForAbstractClass(AbstractClient::class, [$configs]);

        $fullUrl = PHPUnitUtil::callMethod(
            $client,
            'getFullUrl',
            ['/test-endpoint']
        );
        $this->assertEquals(
            Constants::API_URL . '/test-endpoint',
            $fullUrl,
            'Full Url should be ok'
        );
        // formatResponseBody
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

        $response = new Response(MockedData::DECISIONS_FILTER, 404);

        $decodedResponse = PHPUnitUtil::callMethod(
            $client,
            'formatResponseBody',
            [$response]
        );

        $this->assertEquals(
            json_decode(MockedData::DECISIONS_FILTER, true),
            $decodedResponse,
            'No exception for 404'
        );

        // sendRequest
    }

    public function testSendRequest()
    {
        $configs = array_merge($this->configs, ['api_url' => Constants::API_URL]);
        $requestHandler = $this->getCurlMock(['handle']);

        $client = $this->getMockForAbstractClass(AbstractClient::class, [$configs, $requestHandler]);

        $response = new Response(MockedData::DECISIONS_FILTER, 200);

        $requestHandler->method('handle')->willReturn(
            $response
        );

        $decodedResponse = PHPUnitUtil::callMethod(
            $client,
            'request',
            ['GET', '/watcher']
        );

        $this->assertEquals(
            json_decode(MockedData::DECISIONS_FILTER, true),
            $decodedResponse,
            'Decoded response should be correct'
        );

        $error = false;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'request',
                ['PUT', '/watcher']
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Method \(PUT\) is not allowed/',
            $error,
            'Not allowed method should throw error'
        );
    }
}
