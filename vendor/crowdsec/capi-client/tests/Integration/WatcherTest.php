<?php

namespace CrowdSec\CapiClient\Tests\Integration;

/**
 * Integration Test for watcher.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\CapiClient\Client\CapiHandler\Curl;
use CrowdSec\CapiClient\Client\CapiHandler\FileGetContents;
use CrowdSec\CapiClient\ClientException;
use CrowdSec\CapiClient\Constants;
use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Tests\Constants as TestConstants;
use CrowdSec\CapiClient\Tests\PHPUnitUtil;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\Common\Client\AbstractClient;
use CrowdSec\Common\Logger\FileLog;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Exception;

/**
 * @covers \CrowdSec\CapiClient\Watcher::getStreamDecisions
 * @covers \CrowdSec\CapiClient\Watcher::enroll
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::storeScenarios
 *
 * @uses \CrowdSec\CapiClient\Storage\FileStorage::storeToken
 * @uses \CrowdSec\CapiClient\Storage\FileStorage::writeFile
 * @uses \CrowdSec\CapiClient\Watcher::buildSimpleMetrics
 * @uses \CrowdSec\CapiClient\Watcher::handleLogin
 * @uses \CrowdSec\CapiClient\Watcher::login
 *
 * @covers \CrowdSec\CapiClient\Watcher::pushMetrics
 *
 * @uses \CrowdSec\CapiClient\Watcher::normalizeTags
 * @uses \CrowdSec\CapiClient\Watcher::shouldLogin
 * @uses \CrowdSec\CapiClient\Configuration\Signal::getConfigTreeBuilder
 * @uses \CrowdSec\CapiClient\Configuration\Signal\Decisions::cleanConfigs
 * @uses \CrowdSec\CapiClient\Configuration\Signal\Decisions::getConfigTreeBuilder
 * @uses \CrowdSec\CapiClient\Configuration\Signal\Source::getConfigTreeBuilder
 * @uses \CrowdSec\CapiClient\Configuration\Watcher::addMetricsNodes
 * @uses \CrowdSec\CapiClient\Configuration\Watcher::getConfigTreeBuilder
 * @uses \CrowdSec\CapiClient\Signal::__construct
 * @uses \CrowdSec\CapiClient\Signal::configureDecisions
 * @uses \CrowdSec\CapiClient\Signal::configureProperties
 * @uses \CrowdSec\CapiClient\Signal::configureSource
 * @uses \CrowdSec\CapiClient\Signal::toArray
 * @uses \CrowdSec\CapiClient\Storage\FileStorage::__construct
 * @uses \CrowdSec\CapiClient\Storage\FileStorage::getBasePath
 * @uses \CrowdSec\CapiClient\Storage\FileStorage::readFile
 * @uses \CrowdSec\CapiClient\Storage\FileStorage::retrieveMachineId
 * @uses \CrowdSec\CapiClient\Storage\FileStorage::retrievePassword
 * @uses \CrowdSec\CapiClient\Storage\FileStorage::retrieveScenarios
 * @uses \CrowdSec\CapiClient\Storage\FileStorage::retrieveToken
 * @uses \CrowdSec\CapiClient\Watcher::__construct
 * @uses \CrowdSec\CapiClient\Watcher::areEquals
 * @uses \CrowdSec\CapiClient\Watcher::buildSignal
 * @uses \CrowdSec\CapiClient\Watcher::buildSimpleSignalForIp
 * @uses \CrowdSec\CapiClient\Watcher::configure
 * @uses \CrowdSec\CapiClient\Watcher::convertSecondsToDuration
 * @uses \CrowdSec\CapiClient\Watcher::ensureAuth
 * @uses \CrowdSec\CapiClient\Watcher::ensureRegister
 * @uses \CrowdSec\CapiClient\Watcher::formatDate
 * @uses \CrowdSec\CapiClient\Watcher::formatDecisions
 * @uses \CrowdSec\CapiClient\Watcher::formatUserAgent
 * @uses \CrowdSec\CapiClient\Watcher::handleTokenHeader
 * @uses \CrowdSec\CapiClient\Watcher::manageRequest
 * @uses \CrowdSec\CapiClient\Watcher::pushSignals
 * @uses \CrowdSec\CapiClient\Watcher::shouldRefreshCredentials
 * @uses \CrowdSec\CapiClient\Watcher::validateDateInput
 * @uses \CrowdSec\CapiClient\Client\AbstractClient::__construct
 */
final class WatcherTest extends TestCase
{
    protected $configs = [
        'machine_id_prefix' => TestConstants::MACHINE_ID_PREFIX,
        'user_agent_suffix' => TestConstants::USER_AGENT_SUFFIX,
        'scenarios' => ['crowdsecurity/http-backdoors-attempts', 'crowdsecurity/http-bad-user-agent'],
    ];

    /**
     * @var string
     */
    private $debugFile;
    /**
     * @var FileLog
     */
    private $logger;
    /**
     * @var string
     */
    private $prodFile;
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * set up test environment.
     */
    public function setUp(): void
    {
        $this->root = vfsStream::setup('/tmp');
        $currentDate = date('Y-m-d');
        $this->debugFile = 'debug-' . $currentDate . '.log';
        $this->prodFile = 'prod-' . $currentDate . '.log';
        $this->logger = new FileLog(['log_directory_path' => $this->root->url(), 'debug_mode' => true]);
    }

    public function requestHandlerProvider(): array
    {
        return [
            'Default (Curl)' => [null],
            'FileGetContents' => [new FileGetContents()],
        ];
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testDecisionsStream($requestHandler)
    {
        if (file_exists(__DIR__ . '/../../src/Storage/dev-token.json')) {
            // Remove token to force login flow
            file_put_contents(__DIR__ . '/../../src/Storage/dev-token.json', '');
        }
        $client = new Watcher($this->configs, new FileStorage(), $requestHandler, $this->logger);
        $this->checkRequestHandler($client, $requestHandler);
        $response = $client->getStreamDecisions();

        $this->assertArrayHasKey('new', $response, 'Response should have a "new" key');
        $this->assertArrayHasKey('deleted', $response, 'Response should have a "deleted" key');
        $this->assertArrayHasKey('links', $response, 'Response should have a "links" key');
        $this->assertArrayHasKey('blocklists', $response['links'], 'Response links should have a "blocklists" key');
        PHPUnitUtil::assertRegExp(
            $this,
            '/.*100.*"type":"WATCHER_CLIENT_PUSH_METRICS_RESULT.*metrics updated successfully"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Debug log content should be correct'
        );
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testPushSignals($requestHandler)
    {
        $client = new Watcher($this->configs, new FileStorage(), $requestHandler);
        $this->checkRequestHandler($client, $requestHandler);
        $signals = $this->getSignals();
        $response = $client->pushSignals($signals);

        PHPUnitUtil::assertRegExp(
            $this,
            '/OK/',
            $response['message'],
            'Signals should be pushed'
        );

        // With CAPI v3, there is no error even if signal is incomplete
        /*unset($signals[0]['source']);
        $error = '';
        $code = 0;
        try {
            $client->pushSignals([$signals[0]]);
        } catch (ClientException $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
        }

        $this->assertEquals(400, $code);
        PHPUnitUtil::assertRegExp(
            $this,
            '/missing required properties/',
            $error,
            'Should throw an error for bad formatted signal'
        );*/

        // Build Simple Signal
        $signal = $client->buildSimpleSignalForIp(TestConstants::IP, $this->configs['scenarios'][0], null);
        $response = $client->pushSignals([$signal]);

        PHPUnitUtil::assertRegExp(
            $this,
            '/OK/',
            $response['message'],
            'Signals should be pushed'
        );

        // Build Signal
        $properties = [
            'scenario' => $this->configs['scenarios'][0],
            'scenario_trust' => 'certified',
            'scenario_version' => 'v1.2.0',
            'scenario_hash' => 'azertyuiop',
            'created_at' => new \DateTime('2023-01-13T01:34:56.778054Z'),
            'message' => 'This is a test message',
            'start_at' => new \DateTime('2023-01-12T23:48:45.123456Z'),
            'stop_at' => new \DateTime('2022-01-13T01:34:55.432150Z'),
        ];

        $sourceScope = Constants::SCOPE_IP;
        $sourceValue = TestConstants::IP;

        $source = [
            'scope' => $sourceScope,
            'value' => $sourceValue,
        ];

        $decisions = [
            [
                'id' => 1979,
                'duration' => 3600,
                'origin' => 'crowdsec-integration-test',
                'scope' => $sourceScope,
                'value' => $sourceValue,
                'type' => 'custom',
                'simulated' => true,
            ],
        ];

        $signal = $client->buildSignal($properties, $source, $decisions);
        $response = $client->pushSignals([$signal]);

        PHPUnitUtil::assertRegExp(
            $this,
            '/OK/',
            $response['message'],
            'Signals should be pushed'
        );
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testEnroll($requestHandler)
    {
        $enrollmentKey = file_get_contents(__DIR__ . '/.enrollment_key.txt');
        if (!$enrollmentKey) {
            throw new Exception('Error while trying to get content of .enrollment_key.txt file');
        }
        $client = new Watcher($this->configs, new FileStorage(), $requestHandler);
        $this->checkRequestHandler($client, $requestHandler);
        $response = $client->enroll('CAPI CLIENT INTEGRATION TEST', false, $enrollmentKey, ['test-tag']);

        PHPUnitUtil::assertRegExp(
            $this,
            '/OK/',
            $response['message'],
            'Instance should be enrolled'
        );

        $error = '';
        $code = 0;
        try {
            $client->enroll('CAPI CLIENT INTEGRATION TEST', false, 'ThisEnrollmentKeyDoesNotExist', ['test-tag']);
        } catch (ClientException $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
        }

        $this->assertEquals(403, $code);
        PHPUnitUtil::assertRegExp(
            $this,
            '/attachment key provided is not valid/',
            $error,
            'Should throw an error for bad enrollment key'
        );
    }

    /**
     * @return void
     */
    private function checkRequestHandler(AbstractClient $client, $requestHandler)
    {
        if (null === $requestHandler) {
            $this->assertEquals(
                Curl::class,
                get_class($client->getRequestHandler()),
                'Request handler should be curl by default'
            );
        } else {
            $this->assertEquals(
                FileGetContents::class,
                get_class($client->getRequestHandler()),
                'Request handler should be file_get_contents'
            );
        }
    }

    /**
     * @return array[]
     */
    private function getSignals(): array
    {
        return [
            0 => [
                'message' => 'Ip 1.1.1.1 performed "crowdsecurity / http - path - traversal - probing" (6 events over 29.992437958s) at 2020-11-06 20:14:11.189255784 +0000 UTC m=+52.785061338',
                'scenario' => 'crowdsecurity/http-path-traversal-probing',
                'scenario_hash' => '',
                'scenario_version' => '',
                'source' => [
                    'id' => 1,
                    'as_name' => 'CAPI CLIENT PHP INTEGRATION TEST',
                    'cn' => 'FR',
                    'ip' => '1.1.1.1',
                    'latitude' => 48.9917,
                    'longitude' => 1.9097,
                    'range' => '1.1.1.1/32',
                    'scope' => 'test',
                    'value' => '1.1.1.1',
                ],
                'start_at' => '2020-11-06T20:13:41.196817737Z',
                'stop_at' => '2020-11-06T20:14:11.189252228Z',
            ],
            1 => [
                'message' => 'Ip 2.2.2.2 performed "crowdsecurity / http - probing" (6 events over 29.992437958s) at 2020-11-06 20:14:11.189255784 +0000 UTC m=+52.785061338',
                'scenario' => 'crowdsecurity/http-probing',
                'scenario_hash' => '',
                'scenario_version' => '',
                'source' => [
                    'id' => 2,
                    'as_name' => 'CAPI CLIENT PHP INTEGRATION TEST',
                    'cn' => 'FR',
                    'ip' => '2.2.2.2',
                    'latitude' => 48.9917,
                    'longitude' => 1.9097,
                    'range' => '2.2.2.2/32',
                    'scope' => 'test',
                    'value' => '2.2.2.2',
                ],
                'start_at' => '2020-11-06T20:13:41.196817737Z',
                'stop_at' => '2020-11-06T20:14:11.189252228Z',
            ],
        ];
    }
}
