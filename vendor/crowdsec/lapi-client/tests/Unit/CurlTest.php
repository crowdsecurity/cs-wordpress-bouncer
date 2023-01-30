<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient\Tests\Unit;

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

use CrowdSec\LapiClient\Bouncer;
use CrowdSec\LapiClient\ClientException;
use CrowdSec\LapiClient\Constants;
use CrowdSec\LapiClient\HttpMessage\Request;
use CrowdSec\LapiClient\Tests\Constants as TestConstants;
use CrowdSec\LapiClient\Tests\MockedData;
use CrowdSec\LapiClient\Tests\PHPUnitUtil;

/**
 * @uses \CrowdSec\LapiClient\AbstractClient
 * @uses \CrowdSec\LapiClient\HttpMessage\Request
 * @uses \CrowdSec\LapiClient\HttpMessage\Response
 * @uses \CrowdSec\LapiClient\HttpMessage\AbstractMessage
 * @uses \CrowdSec\LapiClient\Configuration::getConfigTreeBuilder
 * @uses \CrowdSec\LapiClient\Bouncer::__construct
 * @uses \CrowdSec\LapiClient\Bouncer::configure
 * @uses \CrowdSec\LapiClient\Bouncer::formatUserAgent
 * @uses \CrowdSec\LapiClient\Configuration::addConnectionNodes
 * @uses \CrowdSec\LapiClient\Configuration::validate
 * @uses \CrowdSec\LapiClient\Configuration::cleanConfigs
 *
 * @covers \CrowdSec\LapiClient\RequestHandler\Curl::createOptions
 * @covers \CrowdSec\LapiClient\RequestHandler\Curl::handle
 * @covers \CrowdSec\LapiClient\Bouncer::getStreamDecisions
 * @covers \CrowdSec\LapiClient\Bouncer::getFilteredDecisions
 * @covers \CrowdSec\LapiClient\Bouncer::manageRequest
 * @covers \CrowdSec\LapiClient\RequestHandler\AbstractRequestHandler::__construct
 * @covers \CrowdSec\LapiClient\RequestHandler\AbstractRequestHandler::getConfig
 * @covers \CrowdSec\LapiClient\RequestHandler\Curl::handleConfigs
 * @covers \CrowdSec\LapiClient\RequestHandler\Curl::handleMethod
 */
final class CurlTest extends AbstractClient
{
    public function testDecisionsStream()
    {
        // Success test
        $mockCurlRequest = $this->getCurlMock();
        $mockCurlRequest->method('exec')->willReturn(
            MockedData::DECISIONS_STREAM_LIST
        );
        $mockCurlRequest->method('getResponseHttpCode')->willReturn(
            MockedData::HTTP_200
        );
        $client = new Bouncer($this->configs, $mockCurlRequest);
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
        $mockCurlRequest = $this->getCurlMock();
        $mockCurlRequest->method('exec')->willReturn(
            MockedData::DECISIONS_FILTER
        );
        $mockCurlRequest->method('getResponseHttpCode')->willReturn(
            MockedData::HTTP_200
        );
        $client = new Bouncer($this->configs, $mockCurlRequest);
        $decisionsResponse = $client->getFilteredDecisions();

        $this->assertEquals(
            json_decode(MockedData::DECISIONS_FILTER, true),
            $decisionsResponse,
            'Success get filtered decisions'
        );
    }

    public function testHandleError()
    {
        $mockCurlRequest = $this->getCurlMock();

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
            'User agent is required',
            $error,
            'Should failed and throw if no user agent'
        );

        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                false
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

        $mockCurlRequest->method('getResponseHttpCode')->willReturn(0);

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
        $url = Constants::DEFAULT_LAPI_URL . '/watchers';
        $method = 'POST';
        $parameters = ['machine_id' => 'test', 'password' => 'test'];
        $configs = $this->configs;

        $client = new Bouncer($configs);
        $curlRequester = $client->getRequestHandler();
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
            \CURLOPT_SSL_VERIFYPEER => false,
            \CURLOPT_ENCODING => ''
        ];

        $this->assertEquals(
            $expected,
            $curlOptions,
            'Curl options must be as expected for POST'
        );

        $url = Constants::DEFAULT_LAPI_URL . '/decisions/stream';
        $method = 'GET';
        $parameters = ['foo' => 'bar', 'crowd' => 'sec'];
        $client = new Bouncer($configs);
        $curlRequester = $client->getRequestHandler();

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
            \CURLOPT_SSL_VERIFYPEER => false,
            \CURLOPT_ENCODING => ''
        ];

        $this->assertEquals(
            $expected,
            $curlOptions,
            'Curl options must be as expected for GET'
        );

        $configs = $this->tlsConfigs;
        $method = 'POST';
        $parameters = ['machine_id' => 'test', 'password' => 'test'];

        $client = new Bouncer($configs);
        $curlRequester = $client->getRequestHandler();
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
}
