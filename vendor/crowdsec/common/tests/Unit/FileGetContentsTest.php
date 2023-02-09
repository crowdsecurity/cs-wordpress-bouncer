<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace CrowdSec\Common\Tests\Unit;

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

use CrowdSec\Common\Client\ClientException;
use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Client\RequestHandler\FileGetContents;
use CrowdSec\Common\Constants;
use CrowdSec\Common\Tests\Constants as TestConstants;
use CrowdSec\Common\Tests\PHPUnitUtil;

/**
 * @uses \CrowdSec\Common\Client\AbstractClient
 * @uses \CrowdSec\Common\Client\HttpMessage\Request
 * @uses \CrowdSec\Common\Client\HttpMessage\Response
 * @uses \CrowdSec\Common\Client\HttpMessage\AbstractMessage
 *
 * @covers \CrowdSec\Common\Client\RequestHandler\FileGetContents::handle
 * @covers \CrowdSec\Common\Client\RequestHandler\FileGetContents::createContextConfig
 * @covers \CrowdSec\Common\Client\RequestHandler\FileGetContents::convertHeadersToString
 * @covers \CrowdSec\Common\Client\RequestHandler\FileGetContents::getResponseHttpCode
 * @covers \CrowdSec\Common\Client\RequestHandler\AbstractRequestHandler::__construct
 * @covers \CrowdSec\Common\Client\RequestHandler\AbstractRequestHandler::getConfig
 */
final class FileGetContentsTest extends AbstractClient
{
    public function testContextConfig()
    {
        $method = 'POST';
        $parameters = ['machine_id' => 'test', 'password' => 'test'];

        $fgcRequester = new FileGetContents();

        $request = new Request('test-url', $method, ['User-Agent' => TestConstants::USER_AGENT_SUFFIX], $parameters);

        $contextConfig = PHPUnitUtil::callMethod(
            $fgcRequester,
            'createContextConfig',
            [$request]
        );

        $contextConfig['http']['header'] = str_replace("\r", '', $contextConfig['http']['header']);

        $expected = [
            'http' => [
                'method' => $method,
                'header' => 'Accept: application/json
Content-Type: application/json
User-Agent: ' . TestConstants::USER_AGENT_SUFFIX . '
',
                'ignore_errors' => true,
                'content' => '{"machine_id":"test","password":"test"}',
                'timeout' => Constants::API_TIMEOUT,
            ],
            'ssl' => [
                'verify_peer' => false,
            ],
        ];

        $this->assertEquals(
            $expected,
            $contextConfig,
            'Context config must be as expected for POST'
        );

        $method = 'GET';
        $parameters = ['foo' => 'bar', 'crowd' => 'sec'];

        $request = new Request('test-url', $method, ['User-Agent' => TestConstants::USER_AGENT_SUFFIX], $parameters);

        $contextConfig = PHPUnitUtil::callMethod(
            $fgcRequester,
            'createContextConfig',
            [$request]
        );

        $contextConfig['http']['header'] = str_replace("\r", '', $contextConfig['http']['header']);

        $expected = [
            'http' => [
                'method' => $method,
                'header' => 'Accept: application/json
Content-Type: application/json
User-Agent: ' . TestConstants::USER_AGENT_SUFFIX . '
',
                'ignore_errors' => true,
                'timeout' => Constants::API_TIMEOUT,
            ],
            'ssl' => [
                'verify_peer' => false,
            ],
        ];

        $this->assertEquals(
            $expected,
            $contextConfig,
            'Context config must be as expected for GET'
        );

        $configs = $this->tlsConfigs;
        $method = 'POST';
        $parameters = ['machine_id' => 'test', 'password' => 'test'];

        $fgcRequester = new FileGetContents($configs);

        $request = new Request('test-url', $method, ['User-Agent' => TestConstants::USER_AGENT_SUFFIX], $parameters);

        $contextConfig = PHPUnitUtil::callMethod(
            $fgcRequester,
            'createContextConfig',
            [$request]
        );

        $contextConfig['http']['header'] = str_replace("\r", '', $contextConfig['http']['header']);

        $expected = [
            'http' => [
                'method' => $method,
                'header' => 'Accept: application/json
Content-Type: application/json
User-Agent: ' . TestConstants::USER_AGENT_SUFFIX . '
',
                'ignore_errors' => true,
                'content' => '{"machine_id":"test","password":"test"}',
                'timeout' => TestConstants::API_TIMEOUT,
            ],
            'ssl' => [
                'verify_peer' => true,
                'local_cert' => 'tls_cert_path_test',
                'local_pk' => 'tls_key_path_test',
                'cafile' => 'tls_ca_cert_path_test',
            ],
        ];

        $this->assertEquals(
            $expected,
            $contextConfig,
            'Context config must be as expected for POST'
        );
    }

    public function testHandleError()
    {
        $mockFGCRequest = $this->getFGCMock(['exec']);

        $request = new Request('test-uri', 'POST', ['User-Agent' => null]);
        $error = false;
        try {
            $mockFGCRequest->handle($request);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        $this->assertEquals(
            'User agent is required',
            $error,
            'Should failed and throw if no user agent'
        );

        $mockFGCRequest = $this->getFGCMock(['exec']);
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                ['header' => []]
            )
        );

        $request = new Request('test-uri', 'POST', ['User-Agent' => TestConstants::USER_AGENT_SUFFIX]);

        $code = 0;
        try {
            $mockFGCRequest->handle($request);
        } catch (ClientException $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
        }

        $this->assertEquals(500, $code);

        $this->assertEquals(
            'Unexpected HTTP call failure.',
            $error,
            'Should failed and throw if no response'
        );
    }

    public function testHandleUrl()
    {
        $mockFGCRequest = $this->getFGCMock(['exec']);

        $request = new Request('test-uri', 'GET', ['User-Agent' => TestConstants::USER_AGENT_SUFFIX], ['foo' => 'bar']);

        $mockFGCRequest->method('exec')
            ->will(
                $this->returnValue(['response' => 'ok'])
            );

        $mockFGCRequest->expects($this->exactly(1))->method('exec')
            ->withConsecutive(
                ['test-uri?foo=bar']
            );
        $mockFGCRequest->handle($request);
    }

    public function testPrivateOrProtectedMethods()
    {
        // getResponseHttpCode
        $fgcRequester = new FileGetContents();

        $parts = ['http', 202];

        $result = PHPUnitUtil::callMethod(
            $fgcRequester,
            'getResponseHttpCode',
            [$parts]
        );
        $this->assertEquals(
            202,
            $result,
            'Response status code should be retrieved'
        );
    }
}
