<?php

namespace CrowdSec\LapiClient\Tests\Integration;

/**
 * Integration Test for bouncer client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\Common\Client\AbstractClient;
use CrowdSec\Common\Client\RequestHandler\FileGetContents;
use CrowdSec\LapiClient\Bouncer;
use CrowdSec\LapiClient\Constants;
use CrowdSec\LapiClient\Tests\Constants as TestConstants;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
final class BouncerTest extends TestCase
{
    /**
     * @var array
     */
    protected $configs;
    /**
     * @var string
     */
    protected $useTls;
    /**
     * @var WatcherClient
     */
    protected $watcherClient;

    private function addTlsConfig(&$bouncerConfigs, $tlsPath)
    {
        $bouncerConfigs['tls_cert_path'] = $tlsPath . '/bouncer.pem';
        $bouncerConfigs['tls_key_path'] = $tlsPath . '/bouncer-key.pem';
        $bouncerConfigs['tls_ca_cert_path'] = $tlsPath . '/ca-chain.pem';
        $bouncerConfigs['tls_verify_peer'] = true;
    }

    protected function setUp(): void
    {
        $this->useTls = (string) getenv('BOUNCER_TLS_PATH');

        $bouncerConfigs = [
            'auth_type' => $this->useTls ? Constants::AUTH_TLS : Constants::AUTH_KEY,
            'api_key' => getenv('BOUNCER_KEY'),
            'api_url' => getenv('LAPI_URL'),
            'user_agent_suffix' => TestConstants::USER_AGENT_SUFFIX,
        ];
        if ($this->useTls) {
            $this->addTlsConfig($bouncerConfigs, $this->useTls);
        }

        $this->configs = $bouncerConfigs;
        $this->watcherClient = new WatcherClient($this->configs);
        // Delete all decisions
        $this->watcherClient->deleteAllDecisions();
    }

    public function requestHandlerProvider(): array
    {
        return [
            'Default (Curl)' => [null],
            'FileGetContents' => ['FileGetContents'],
        ];
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testDecisionsStream($requestHandler)
    {
        if ('FileGetContents' === $requestHandler) {
            $client = new Bouncer($this->configs, new FileGetContents($this->configs));
        } else {
            // Curl by default
            $client = new Bouncer($this->configs);
        }
        if ($this->useTls) {
            $this->assertEquals(Constants::AUTH_TLS, $this->configs['auth_type']);
        } else {
            $this->assertEquals(Constants::AUTH_KEY, $this->configs['auth_type']);
        }
        $this->checkRequestHandler($client, $requestHandler);
        // Call for start up with no active decisions
        $response = $client->getStreamDecisions(true);

        $this->assertArrayHasKey('new', $response, 'Response should have a "new" key');
        $this->assertNull($response['new'], 'Should be no new decision yet');
        $this->assertArrayHasKey('deleted', $response, 'Response should have a "deleted" key');

        // Add decisions
        $now = new \DateTime();
        $this->watcherClient->addDecision($now, '12h', '+12 hours', TestConstants::BAD_IP, 'captcha');
        $this->watcherClient->addDecision($now, '24h', '+24 hours', TestConstants::BAD_IP . '/' . TestConstants::IP_RANGE, 'ban');
        $this->watcherClient->addDecision($now, '24h', '+24 hours', TestConstants::JAPAN, 'captcha', Constants::SCOPE_COUNTRY);
        // Retrieve default decisions (Ip and Range) without startup
        $response = $client->getStreamDecisions(false);
        $this->assertCount(2, $response['new'], 'Should be 2 active decisions for default scopes Ip and Range');
        // Retrieve all decisions (Ip, Range and Country) with startup
        $response = $client->getStreamDecisions(
            true,
            [
                'scopes' => Constants::SCOPE_IP . ',' . Constants::SCOPE_RANGE . ',' . Constants::SCOPE_COUNTRY,
            ]
        );
        $this->assertCount(3, $response['new'], 'Should be 3 active decisions for all scopes');
        // Retrieve all decisions (Ip, Range and Country) without startup
        $response = $client->getStreamDecisions(
            false,
            [
                'scopes' => Constants::SCOPE_IP . ',' . Constants::SCOPE_RANGE . ',' . Constants::SCOPE_COUNTRY,
            ]
        );
        $this->assertNull($response['new'], 'Should be no new if startup has been done');
        // Delete all decisions
        $this->watcherClient->deleteAllDecisions();
        $response = $client->getStreamDecisions(
            false,
            [
                'scopes' => Constants::SCOPE_IP . ',' . Constants::SCOPE_RANGE . ',' . Constants::SCOPE_COUNTRY,
            ]
        );
        $this->assertNull($response['new'], 'Should be no new decision yet');
        $this->assertNotNull($response['deleted'], 'Should be deleted decisions now');
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testFilteredDecisions($requestHandler)
    {
        if ('FileGetContents' === $requestHandler) {
            $client = new Bouncer($this->configs, new FileGetContents($this->configs));
        } else {
            // Curl by default
            $client = new Bouncer($this->configs);
        }
        if ($this->useTls) {
            $this->assertEquals(Constants::AUTH_TLS, $this->configs['auth_type']);
        } else {
            $this->assertEquals(Constants::AUTH_KEY, $this->configs['auth_type']);
        }
        $this->checkRequestHandler($client, $requestHandler);

        $response = $client->getFilteredDecisions(['ip' => TestConstants::BAD_IP]);
        $this->assertCount(0, $response, 'No decisions yet');
        // Add decisions
        $now = new \DateTime();
        $this->watcherClient->addDecision($now, '12h', '+12 hours', TestConstants::BAD_IP, 'captcha');
        $this->watcherClient->addDecision($now, '24h', '+24 hours', '1.2.3.0/' . TestConstants::IP_RANGE, 'ban');
        $this->watcherClient->addDecision($now, '24h', '+24 hours', TestConstants::JAPAN, 'captcha', Constants::SCOPE_COUNTRY);
        $response = $client->getFilteredDecisions(['ip' => TestConstants::BAD_IP]);
        $this->assertCount(2, $response, '2 decisions for specified IP');
        $response = $client->getFilteredDecisions(['scope' => Constants::SCOPE_COUNTRY, 'value' => TestConstants::JAPAN]);
        $this->assertCount(1, $response, '1 decision for specified country');
        $response = $client->getFilteredDecisions(['range' => '1.2.3.0/' . TestConstants::IP_RANGE]);
        $this->assertCount(1, $response, '1 decision for specified range');
        $response = $client->getFilteredDecisions(['ip' => '2.3.4.5']);
        $this->assertCount(0, $response, '0 decision for specified IP');
        $response = $client->getFilteredDecisions(['type' => 'captcha']);
        $this->assertCount(2, $response, '2 decision for specified type');
        // Delete all decisions
        $this->watcherClient->deleteAllDecisions();
        $response = $client->getFilteredDecisions(['ip' => TestConstants::BAD_IP]);
        $this->assertCount(0, $response, '0 decision after delete for specified IP');
    }

    /**
     * @return void
     */
    private function checkRequestHandler(AbstractClient $client, $requestHandler)
    {
        if (null === $requestHandler) {
            $this->assertInstanceOf(
                'CrowdSec\Common\Client\RequestHandler\Curl',
                $client->getRequestHandler(),
                'Request handler should be curl by default'
            );
        } else {
            $this->assertInstanceOf(
                'CrowdSec\Common\Client\RequestHandler\FileGetContents',
                $client->getRequestHandler(),
                'Request handler should be file_get_contents'
            );
        }
    }
}
