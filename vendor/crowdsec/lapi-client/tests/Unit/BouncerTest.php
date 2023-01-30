<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient\Tests\Unit;

/**
 * Test for watcher requests.
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
use CrowdSec\LapiClient\HttpMessage\Response;
use CrowdSec\LapiClient\Tests\Constants as TestConstants;
use CrowdSec\LapiClient\Tests\MockedData;
use CrowdSec\LapiClient\Tests\PHPUnitUtil;

/**
 * @uses \CrowdSec\LapiClient\AbstractClient
 * @uses \CrowdSec\LapiClient\HttpMessage\Response
 * @uses \CrowdSec\LapiClient\HttpMessage\Request
 * @uses \CrowdSec\LapiClient\HttpMessage\AbstractMessage::getHeaders
 * @uses \CrowdSec\LapiClient\RequestHandler\Curl::createOptions
 * @uses \CrowdSec\LapiClient\RequestHandler\Curl::handle
 * @uses \CrowdSec\LapiClient\RequestHandler\AbstractRequestHandler::__construct
 *
 *
 *
 * @covers \CrowdSec\LapiClient\Configuration::cleanConfigs
 * @covers \CrowdSec\LapiClient\Bouncer::__construct
 * @covers \CrowdSec\LapiClient\Bouncer::configure
 * @covers \CrowdSec\LapiClient\Bouncer::manageRequest
 * @covers \CrowdSec\LapiClient\Bouncer::getStreamDecisions
 * @covers \CrowdSec\LapiClient\Bouncer::getFilteredDecisions
 * @covers \CrowdSec\LapiClient\AbstractClient::request
 * @covers \CrowdSec\LapiClient\Bouncer::formatUserAgent
 * @covers \CrowdSec\LapiClient\Configuration::getConfigTreeBuilder
 * @covers \CrowdSec\LapiClient\Configuration::addConnectionNodes
 * @covers \CrowdSec\LapiClient\Configuration::validate
 */
final class BouncerTest extends AbstractClient
{
    public function testDecisionsStreamParams()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\LapiClient\Bouncer')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs])
            ->onlyMethods(['request'])
            ->getMock();

        $mockClient->expects($this->exactly(1))->method('request')
            ->withConsecutive(
                [
                    'GET',
                    Bouncer::DECISIONS_STREAM_ENDPOINT,
                    ['startup' => true],
                    [
                        'User-Agent' => Constants::USER_AGENT_PREFIX . '_' . TestConstants::USER_AGENT_SUFFIX
                                        . '/' . TestConstants::USER_AGENT_VERSION,
                        'X-Api-Key' => TestConstants::API_KEY,
                    ],
                ]
            );
        $mockClient->getStreamDecisions(true);
    }

    public function testFilteredDecisionsParams()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\LapiClient\Bouncer')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs])
            ->onlyMethods(['request'])
            ->getMock();

        $mockClient->expects($this->exactly(1))->method('request')
            ->withConsecutive(
                [
                    'GET',
                    Bouncer::DECISIONS_FILTER_ENDPOINT,
                    ['ip' => '1.2.3.4'],
                    [
                        'User-Agent' => Constants::USER_AGENT_PREFIX . '_' . TestConstants::USER_AGENT_SUFFIX
                                        . '/' . TestConstants::USER_AGENT_VERSION,
                        'X-Api-Key' => TestConstants::API_KEY,
                    ],
                ]
            );
        $mockClient->getFilteredDecisions(['ip' => '1.2.3.4']);
    }

    public function testRequest()
    {
        // Test a valid POST request and its return

        $mockCurl = $this->getCurlMock(['handle']);

        $mockClient = $this->getMockBuilder('CrowdSec\LapiClient\Bouncer')
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                'configs' => $this->configs,
                'requestHandler' => $mockCurl,
            ])
            ->onlyMethods(['sendRequest'])
            ->getMock();

        $mockCurl->expects($this->exactly(1))->method('handle')->will($this->returnValue(
            new Response(MockedData::DECISIONS_FILTER, MockedData::HTTP_200, [])
        ));

        $response = PHPUnitUtil::callMethod(
            $mockClient,
            'request',
            ['POST', '', [], []]
        );

        $this->assertEquals(
            json_decode(MockedData::DECISIONS_FILTER, true),
            $response,
            'Should format response as expected'
        );

        // Test a not allowed request method (PUT)
        $error = '';
        try {
            PHPUnitUtil::callMethod(
                $mockClient,
                'request',
                ['PUT', '', [], []]
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/not allowed/',
            $error,
            'Not allowed method should throw an exception before sending request'
        );
    }

    public function testConfigure()
    {
        $client = new Bouncer($this->configs);
        // url
        $this->assertEquals(
            Constants::DEFAULT_LAPI_URL,
            $client->getConfig('api_url'),
            'Url should be configured by default'
        );
        // user agent suffix
        $this->assertEquals(
            TestConstants::USER_AGENT_SUFFIX,
            $client->getConfig('user_agent_suffix'),
            'User agent suffix should be configured'
        );
        $error = '';
        try {
            new Bouncer(['user_agent_suffix' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa']);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Length must be <= 16/',
            $error,
            'user_agent_suffix length should be <16'
        );

        $error = '';
        try {
            new Bouncer(['api_key' => TestConstants::API_KEY, 'user_agent_suffix' => 'aaaaa  a']);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Allowed chars are/',
            $error,
            'user_agent_suffix should contain allowed chars'
        );

        $client = new Bouncer(['api_key' => TestConstants::API_KEY, 'user_agent_suffix' => '']);

        $this->assertEquals(
            '',
            $client->getConfig('user_agent_suffix'),
            'user_agent_suffix can be empty'
        );
        // user agent version
        $client = new Bouncer(['api_key' => '1111', 'user_agent_version' => 'v4.56.7']);

        $this->assertEquals(
            'v4.56.7',
            $client->getConfig('user_agent_version'),
            'user_agent_version should be configurable'
        );

        $error = '';
        try {
            new Bouncer(['api_key' => TestConstants::API_KEY, 'user_agent_version' => '']);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Invalid user agent version/',
            $error,
            'user_agent_version can not be empty'
        );


        // auth type
        $error = '';
        try {
            new Bouncer(['auth_type' => 'custom']);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Permissible values:/',
            $error,
            'auth type should be api_key or tls'
        );
        // api _key
        $error = '';
        try {
            new Bouncer([]);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Api key is required/',
            $error,
            'api key should be required'
        );
        // tls conf
        $error = '';
        try {
            new Bouncer(['auth_type' => Constants::AUTH_TLS]);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Bouncer certificate and key paths are required/',
            $error,
            'cert and key path should be required'
        );

        $error = '';
        try {
            new Bouncer(['auth_type' => Constants::AUTH_TLS, 'tls_cert_path' => 'test', 'tls_key_path' => 'test', 'tls_verify_peer' => true]);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/CA path is required/',
            $error,
            'CA cert path should be required if verify peer is true'
        );
        // Unexpected conf
        $client = new Bouncer(['api_key' => '1111', 'user_agent_version' => 'v4.56.7', 'unexpected' => true]);

        $this->assertEquals(
            'v4.56.7',
            $client->getConfig('user_agent_version'),
            'user_agent_version should be configurable'
        );

        $this->assertEquals(
            null,
            $client->getConfig('unexpected'),
            'Unexpected config should have been removed with no exception'
        );

    }
}
