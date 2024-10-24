<?php

declare(strict_types=1);

namespace CrowdSec\Common\Tests\Unit;

/**
 * Test for Curl request handler.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\Common\Client\ClientException;
use CrowdSec\Common\Client\HttpMessage\AppSecRequest;
use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Client\HttpMessage\Response;
use CrowdSec\Common\Client\RequestHandler\Curl;
use CrowdSec\Common\Client\TimeoutException;
use CrowdSec\Common\Constants;
use CrowdSec\Common\Tests\Constants as TestConstants;
use CrowdSec\Common\Tests\MockedData;
use CrowdSec\Common\Tests\PHPUnitUtil;

/**
 * @uses \CrowdSec\Common\Client\AbstractClient
 * @uses \CrowdSec\Common\Client\HttpMessage\Request
 * @uses \CrowdSec\Common\Client\HttpMessage\AppSecRequest
 * @uses \CrowdSec\Common\Client\HttpMessage\Response
 * @uses \CrowdSec\Common\Client\HttpMessage\AbstractMessage
 *
 * @covers \CrowdSec\Common\Client\RequestHandler\Curl::createOptions
 * @covers \CrowdSec\Common\Client\RequestHandler\Curl::handle
 * @covers \CrowdSec\Common\Client\RequestHandler\AbstractRequestHandler::__construct
 * @covers \CrowdSec\Common\Client\RequestHandler\AbstractRequestHandler::getConfig
 * @covers \CrowdSec\Common\Client\RequestHandler\Curl::handleSSL
 * @covers \CrowdSec\Common\Client\RequestHandler\Curl::handleTimeout
 * @covers \CrowdSec\Common\Client\RequestHandler\Curl::handleMethod
 * @covers \CrowdSec\Common\Client\RequestHandler\AbstractRequestHandler::getTimeout
 * @covers \CrowdSec\Common\Client\RequestHandler\AbstractRequestHandler::getConnectTimeout
 * @covers \CrowdSec\Common\Client\RequestHandler\Curl::getConnectTimeoutOption
 * @covers \CrowdSec\Common\Client\RequestHandler\Curl::getTimeoutOption
 */
final class CurlTest extends AbstractClient
{
    public function testHandleError()
    {
        $mockCurlRequest = $this->getCurlMock(['exec', 'getResponseHttpCode']);
        // Test 1 : User agent required
        $request = new Request('test-uri', 'POST', ['User-Agent' => null]);
        $error = '';
        $code = 0;
        try {
            $mockCurlRequest->handle($request);
        } catch (ClientException $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
        }

        $this->assertEquals(400, $code);

        $this->assertEquals(
            'Header "User-Agent" is required',
            $error,
            'Should failed and throw if no user agent'
        );
        // Test 1 bis AppSec header required
        $request = new AppSecRequest('test-uri', 'POST', ['User-Agent' => 'ok']);
        $error = '';
        $code = 0;
        try {
            $mockCurlRequest->handle($request);
        } catch (ClientException $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
        }

        $this->assertEquals(400, $code);

        $this->assertEquals(
            'Header "X-Crowdsec-Appsec-Ip" is required',
            $error,
            'Should failed and throw if no user agent'
        );
        // Test 2 : no response
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                false, // Test 2
                MockedData::DECISIONS_FILTER // Test 3
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(
                202, // Test 3
                0 // Test 4
            )
        );

        $request = new Request('test-uri', 'POST', ['User-Agent' => TestConstants::USER_AGENT_SUFFIX]);

        $code = 0;
        try {
            $mockCurlRequest->handle($request);
        } catch (ClientException $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
        }

        $this->assertEquals(500, $code);

        $this->assertEquals(
            'Unexpected CURL call failure: ',
            $error,
            'Should failed and throw if no response'
        );
        // Test 3: response ok
        $response = $mockCurlRequest->handle($request);
        $this->assertEquals(
            new Response(MockedData::DECISIONS_FILTER, 202),
            $response,
            'Decoded response should be correct'
        );

        // Test 4 : no response status
        $error = false;
        try {
            $mockCurlRequest->handle($request);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        $this->assertEquals(
            'Unexpected empty response http code',
            $error,
            'Should failed and throw if no response status'
        );
    }

    public function testOptions()
    {
        $url = TestConstants::API_URL . '/watchers';
        $method = 'POST';
        $parameters = ['machine_id' => 'test', 'password' => 'test'];
        $configs = $this->configs;

        $curlRequester = new Curl($configs);
        $request = new Request($url, $method, ['User-Agent' => TestConstants::USER_AGENT_SUFFIX], $parameters);

        $curlOptions = PHPUnitUtil::callMethod(
            $curlRequester,
            'createOptions',
            [$request]
        );
        $expected = [
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_USERAGENT => TestConstants::USER_AGENT_SUFFIX,
            \CURLOPT_HTTPHEADER => [
                'Accept:application/json',
                'Content-Type:application/json',
                'User-Agent:' . TestConstants::USER_AGENT_SUFFIX,
            ],
            \CURLOPT_POST => true,
            \CURLOPT_POSTFIELDS => '{"machine_id":"test","password":"test"}',
            \CURLOPT_URL => $url,
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_TIMEOUT => TestConstants::API_TIMEOUT,
            \CURLOPT_CONNECTTIMEOUT => Constants::API_CONNECT_TIMEOUT,
            \CURLOPT_SSL_VERIFYPEER => false,
            \CURLOPT_ENCODING => '',
        ];

        $this->assertEquals(
            $expected,
            $curlOptions,
            'Curl options must be as expected for POST'
        );

        $url = TestConstants::API_URL . '/decisions/stream';
        $method = 'GET';
        $parameters = ['foo' => 'bar', 'crowd' => 'sec'];
        $curlRequester = new Curl($configs);

        $request = new Request($url, $method, ['User-Agent' => TestConstants::USER_AGENT_SUFFIX], $parameters);

        $curlOptions = PHPUnitUtil::callMethod(
            $curlRequester,
            'createOptions',
            [$request]
        );

        $expected = [
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_USERAGENT => TestConstants::USER_AGENT_SUFFIX,
            \CURLOPT_HTTPHEADER => [
                'Accept:application/json',
                'Content-Type:application/json',
                'User-Agent:' . TestConstants::USER_AGENT_SUFFIX,
            ],
            \CURLOPT_POST => false,
            \CURLOPT_HTTPGET => true,
            \CURLOPT_URL => $url . '?foo=bar&crowd=sec',
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_TIMEOUT => TestConstants::API_TIMEOUT,
            \CURLOPT_CONNECTTIMEOUT => Constants::API_CONNECT_TIMEOUT,
            \CURLOPT_SSL_VERIFYPEER => false,
            \CURLOPT_ENCODING => '',
        ];

        $this->assertEquals(
            $expected,
            $curlOptions,
            'Curl options must be as expected for GET'
        );

        $configs = $this->tlsConfigs;
        $method = 'POST';
        $parameters = ['machine_id' => 'test', 'password' => 'test'];

        $curlRequester = new Curl($configs);
        $request = new Request($url, $method, ['User-Agent' => TestConstants::USER_AGENT_SUFFIX], $parameters);

        $curlOptions = PHPUnitUtil::callMethod(
            $curlRequester,
            'createOptions',
            [$request]
        );
        $expected = [
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_USERAGENT => TestConstants::USER_AGENT_SUFFIX,
            \CURLOPT_HTTPHEADER => [
                'Accept:application/json',
                'Content-Type:application/json',
                'User-Agent:' . TestConstants::USER_AGENT_SUFFIX,
            ],
            \CURLOPT_POST => true,
            \CURLOPT_POSTFIELDS => '{"machine_id":"test","password":"test"}',
            \CURLOPT_URL => $url,
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_TIMEOUT => TestConstants::API_TIMEOUT,
            \CURLOPT_CONNECTTIMEOUT => Constants::API_CONNECT_TIMEOUT,
            \CURLOPT_SSL_VERIFYPEER => true,
            \CURLOPT_ENCODING => '',
            \CURLOPT_SSLCERT => 'tls_cert_path_test',
            \CURLOPT_SSLKEY => 'tls_key_path_test',
            \CURLOPT_CAINFO => 'tls_ca_cert_path_test',
        ];

        $this->assertEquals(
            $expected,
            $curlOptions,
            'Curl options must be as expected for POST'
        );

        $method = 'DELETE';
        $request = new Request($url, $method, ['User-Agent' => TestConstants::USER_AGENT_SUFFIX], $parameters);

        $curlOptions = PHPUnitUtil::callMethod(
            $curlRequester,
            'createOptions',
            [$request]
        );
        $expected = [
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_USERAGENT => TestConstants::USER_AGENT_SUFFIX,
            \CURLOPT_HTTPHEADER => [
                'Accept:application/json',
                'Content-Type:application/json',
                'User-Agent:' . TestConstants::USER_AGENT_SUFFIX,
            ],
            \CURLOPT_POST => false,
            \CURLOPT_ENCODING => '',
            \CURLOPT_URL => $url,
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_TIMEOUT => TestConstants::API_TIMEOUT,
            \CURLOPT_CONNECTTIMEOUT => Constants::API_CONNECT_TIMEOUT,
            \CURLOPT_SSL_VERIFYPEER => true,
            \CURLOPT_SSLCERT => 'tls_cert_path_test',
            \CURLOPT_SSLKEY => 'tls_key_path_test',
            \CURLOPT_CAINFO => 'tls_ca_cert_path_test',
        ];

        $this->assertEquals(
            $expected,
            $curlOptions,
            'Curl options must be as expected for DELETE'
        );
    }

    public function testOptionsForAppSec()
    {
        $url = TestConstants::APPSEC_URL . '/';
        $method = 'POST';
        $headers = [
            'X-Crowdsec-Appsec-Ip' => 'test-value',
            'X-Crowdsec-Appsec-Host' => 'test-value',
            'X-Crowdsec-Appsec-User-Agent' => 'test-value',
            'X-Crowdsec-Appsec-Verb' => 'test-value',
            'X-Crowdsec-Appsec-Method' => 'test-value',
            'X-Crowdsec-Appsec-Uri' => 'test-value',
            'X-Crowdsec-Appsec-Api-Key' => 'test-value',
            'Host' => 'test-value.com',
            'Content-Length' => '123',
        ];
        $rawBody = 'this is raw body';
        $configs = $this->tlsConfigs;

        $curlRequester = new Curl($configs);
        $request = new AppSecRequest($url, 'POST', $headers, $rawBody);

        $curlOptions = PHPUnitUtil::callMethod(
            $curlRequester,
            'createOptions',
            [$request]
        );
        $expected = [
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_HTTPHEADER => [
                'X-Crowdsec-Appsec-Ip:test-value',
                'X-Crowdsec-Appsec-Host:test-value',
                'X-Crowdsec-Appsec-User-Agent:test-value',
                'X-Crowdsec-Appsec-Verb:test-value',
                'X-Crowdsec-Appsec-Method:test-value',
                'X-Crowdsec-Appsec-Uri:test-value',
                'X-Crowdsec-Appsec-Api-Key:test-value',
                'Host:test-value.com',
                'Content-Length:123',
            ],
            \CURLOPT_POST => true,
            \CURLOPT_POSTFIELDS => 'this is raw body',
            \CURLOPT_URL => $url,
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_TIMEOUT_MS => TestConstants::APPSEC_TIMEOUT_MS,
            \CURLOPT_CONNECTTIMEOUT_MS => Constants::APPSEC_CONNECT_TIMEOUT_MS,
            \CURLOPT_SSL_VERIFYPEER => false,
            \CURLOPT_ENCODING => '',
        ];

        $this->assertEquals(
            $expected,
            $curlOptions,
            'Curl options must be as expected for POST'
        );

        $method = 'GET';
        $curlRequester = new Curl($configs);

        $request = new AppSecRequest($url, $method, array_merge($headers, ['User-Agent' => TestConstants::USER_AGENT_SUFFIX]));

        $curlOptions = PHPUnitUtil::callMethod(
            $curlRequester,
            'createOptions',
            [$request]
        );

        $expected = [
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_USERAGENT => TestConstants::USER_AGENT_SUFFIX,
            \CURLOPT_HTTPHEADER => [
                'X-Crowdsec-Appsec-Ip:test-value',
                'X-Crowdsec-Appsec-Host:test-value',
                'X-Crowdsec-Appsec-User-Agent:test-value',
                'X-Crowdsec-Appsec-Verb:test-value',
                'X-Crowdsec-Appsec-Method:test-value',
                'X-Crowdsec-Appsec-Uri:test-value',
                'X-Crowdsec-Appsec-Api-Key:test-value',
                'Host:test-value.com',
                'Content-Length:123',
                'User-Agent:' . TestConstants::USER_AGENT_SUFFIX,
            ],
            \CURLOPT_POST => false,
            \CURLOPT_HTTPGET => true,
            \CURLOPT_URL => $url,
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_TIMEOUT_MS => TestConstants::APPSEC_TIMEOUT_MS,
            \CURLOPT_CONNECTTIMEOUT_MS => Constants::APPSEC_CONNECT_TIMEOUT_MS,
            \CURLOPT_SSL_VERIFYPEER => false,
            \CURLOPT_ENCODING => '',
        ];

        $this->assertEquals(
            $expected,
            $curlOptions,
            'Curl options must be as expected for GET'
        );
    }

    public function testHandleThrowsTimeoutException()
    {
        // Mock the Request object
        $request = $this->createMock(Request::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getParams')->willReturn([]);
        $request->method('getUri')->willReturn('http://example.com');

        $mockCurlRequest = $this->getCurlMock(['exec', 'errno', 'error']);

        $mockCurlRequest->method('exec')->willReturn(false);
        $mockCurlRequest->method('errno')->willReturn(\CURLE_OPERATION_TIMEOUTED);
        $mockCurlRequest->method('error')->willReturn('Operation timed out');

        // Test that TimeoutException is thrown
        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('CURL call timeout: Operation timed out');
        $this->expectExceptionCode(500);

        // Call the handle method
        $mockCurlRequest->handle($request);
    }

    public function testHandleThrowsClientException()
    {
        // Mock the Request object
        $request = $this->createMock(Request::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getParams')->willReturn([]);
        $request->method('getUri')->willReturn('http://example.com');

        $mockCurlRequest = $this->getCurlMock(['exec', 'errno', 'error']);

        $mockCurlRequest->method('exec')->willReturn(false);
        $mockCurlRequest->method('errno')->willReturn(\CURLE_HTTP_POST_ERROR);
        $mockCurlRequest->method('error')->willReturn('There was an error');

        // Test that ClientException is thrown
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Unexpected CURL call failure: There was an error');
        $this->expectExceptionCode(500);

        // Call the handle method
        $mockCurlRequest->handle($request);
    }
}
