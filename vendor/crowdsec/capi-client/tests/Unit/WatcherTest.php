<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Tests\Unit;

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

use CrowdSec\CapiClient\ClientException;
use CrowdSec\CapiClient\Constants;
use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Tests\Constants as TestConstants;
use CrowdSec\CapiClient\Tests\MockedData;
use CrowdSec\CapiClient\Tests\PHPUnitUtil;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\Common\Client\ClientException as CommonClientException;
use CrowdSec\Common\Client\HttpMessage\Response;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Util\Test;

/**
 * @uses \CrowdSec\CapiClient\Storage\FileStorage
 * @uses \CrowdSec\CapiClient\Watcher::shouldLogin
 *
 * @covers \CrowdSec\CapiClient\Configuration\Signal\Decisions::cleanConfigs
 * @covers \CrowdSec\CapiClient\Watcher::__construct
 * @covers \CrowdSec\CapiClient\Watcher::configure
 * @covers \CrowdSec\CapiClient\Watcher::login
 * @covers \CrowdSec\CapiClient\Watcher::register
 * @covers \CrowdSec\CapiClient\Watcher::manageRequest
 * @covers \CrowdSec\CapiClient\Watcher::ensureRegister
 * @covers \CrowdSec\CapiClient\Watcher::ensureAuth
 * @covers \CrowdSec\CapiClient\Watcher::getStreamDecisions
 * @covers \CrowdSec\CapiClient\Watcher::pushSignals
 * @covers \CrowdSec\CapiClient\Watcher::enroll
 * @covers \CrowdSec\CapiClient\Watcher::handleTokenHeader
 * @covers \CrowdSec\CapiClient\Watcher::formatUserAgent
 * @covers \CrowdSec\CapiClient\Watcher::generatePassword
 * @covers \CrowdSec\CapiClient\Watcher::generateRandomString
 * @covers \CrowdSec\CapiClient\Watcher::generateMachineId
 * @covers \CrowdSec\CapiClient\Watcher::shouldRefreshCredentials
 * @covers \CrowdSec\CapiClient\Configuration\Watcher::getConfigTreeBuilder
 * @covers \CrowdSec\CapiClient\Watcher::handleLogin
 * @covers \CrowdSec\CapiClient\Watcher::refreshCredentials
 * @covers \CrowdSec\CapiClient\Watcher::normalizeTags
 * @covers \CrowdSec\CapiClient\Configuration\Signal::getConfigTreeBuilder
 * @covers \CrowdSec\CapiClient\Configuration\Signal\Decisions::getConfigTreeBuilder
 * @covers \CrowdSec\CapiClient\Configuration\Signal\Source::getConfigTreeBuilder
 * @covers \CrowdSec\CapiClient\Signal::__construct
 * @covers \CrowdSec\CapiClient\Signal::configureDecisions
 * @covers \CrowdSec\CapiClient\Signal::configureProperties
 * @covers \CrowdSec\CapiClient\Signal::configureSource
 * @covers \CrowdSec\CapiClient\Signal::toArray
 * @covers \CrowdSec\CapiClient\Watcher::convertSecondsToDuration
 * @covers \CrowdSec\CapiClient\Watcher::buildSignal
 * @covers \CrowdSec\CapiClient\Watcher::buildSimpleSignalForIp
 * @covers \CrowdSec\CapiClient\Watcher::formatDate
 * @covers \CrowdSec\CapiClient\Watcher::formatDecisions
 * @covers \CrowdSec\CapiClient\Watcher::validateDateInput
 * @covers \CrowdSec\CapiClient\Watcher::areEquals
 */
final class WatcherTest extends AbstractClient
{
    public function testRegisterParams()
    {
        $mockFileStorage = $this->getFileStorageMock();
        // Set null password to force register
        $mockFileStorage->method('retrievePassword')->willReturn(
            null
        );

        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs, 'storage' => $mockFileStorage])
            ->onlyMethods(['request'])
            ->getMock();
        $mockClient->expects($this->exactly(1))->method('request')
            ->with(
                'POST',
                Constants::REGISTER_ENDPOINT,
                self::callback(function ($params): bool {
                    return 2 === count($params) &&
                           !empty($params['password']) &&
                           Constants::PASSWORD_LENGTH === strlen($params['password']) &&
                           !empty($params['machine_id']) &&
                           Constants::MACHINE_ID_LENGTH === strlen($params['machine_id']) &&
                           0 === substr_compare(
                               $params['machine_id'],
                               TestConstants::MACHINE_ID_PREFIX,
                               0,
                               strlen(TestConstants::MACHINE_ID_PREFIX)
                           );
                }), ['User-Agent' => Constants::USER_AGENT_PREFIX . '_' . TestConstants::USER_AGENT_SUFFIX
                                     . '/' . TestConstants::USER_AGENT_VERSION, ]
            );

        PHPUnitUtil::callMethod(
            $mockClient,
            'ensureRegister',
            []
        );
    }

    public function testLoginParams()
    {
        $mockFileStorage = $this->getFileStorageMock();

        $mockFileStorage->method('retrievePassword')->willReturn(
            TestConstants::PASSWORD
        );
        $mockFileStorage->method('retrieveMachineId')->willReturn(
            TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID
        );
        // Set null token to force login
        $mockFileStorage->method('retrieveToken')->willReturn(
            null
        );
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs, 'storage' => $mockFileStorage])
            ->onlyMethods(['request'])
            ->getMock();
        $mockClient->expects($this->exactly(1))->method('request')
            ->with(
                'POST',
                Constants::LOGIN_ENDPOINT,
                [
                    'password' => TestConstants::PASSWORD,
                    'machine_id' => TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID,
                    'scenarios' => TestConstants::SCENARIOS,
                ],
                [
                    'User-Agent' => Constants::USER_AGENT_PREFIX . '_' . TestConstants::USER_AGENT_SUFFIX
                                    . '/' . TestConstants::USER_AGENT_VERSION,
                ]
            );
        $code = 0;
        $message = '';
        try {
            PHPUnitUtil::callMethod(
                $mockClient,
                'ensureAuth',
                []
            );
        } catch (ClientException $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
        }
        $this->assertEquals(401, $code);
        $this->assertEquals('Login response does not contain required token.', $message);
    }

    public function testSignalsParams()
    {
        $mockFileStorage = $this->getFileStorageMock();
        $mockFileStorage->method('retrievePassword')->willReturn(
            TestConstants::PASSWORD
        );
        $mockFileStorage->method('retrieveMachineId')->willReturn(
            TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID
        );
        $mockFileStorage->method('retrieveToken')->willReturn(
            TestConstants::TOKEN
        );
        $mockFileStorage->method('retrieveScenarios')->willReturn(
            TestConstants::SCENARIOS
        );

        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs, 'storage' => $mockFileStorage])
            ->onlyMethods(['request'])
            ->getMock();

        $signals = ['test'];

        $mockClient->expects($this->exactly(1))->method('request')
            ->withConsecutive(
                [
                    'POST',
                    Constants::SIGNALS_ENDPOINT,
                    $signals,
                    [
                        'User-Agent' => Constants::USER_AGENT_PREFIX . '_' . TestConstants::USER_AGENT_SUFFIX
                                        . '/' . TestConstants::USER_AGENT_VERSION,
                        'Authorization' => 'Bearer ' . TestConstants::TOKEN,
                    ],
                ]
            );
        $mockClient->pushSignals($signals);
    }

    public function testDecisionsStreamParams()
    {
        $mockFileStorage = $this->getFileStorageMock();
        $mockFileStorage->method('retrievePassword')->willReturn(
            TestConstants::PASSWORD
        );
        $mockFileStorage->method('retrieveMachineId')->willReturn(
            TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID
        );
        $mockFileStorage->method('retrieveToken')->willReturn(
            TestConstants::TOKEN
        );
        $mockFileStorage->method('retrieveScenarios')->willReturn(
            TestConstants::SCENARIOS
        );
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs, 'storage' => $mockFileStorage])
            ->onlyMethods(['request'])
            ->getMock();

        $mockClient->expects($this->exactly(1))->method('request')
            ->withConsecutive(
                [
                    'GET',
                    Constants::DECISIONS_STREAM_ENDPOINT,
                    [],
                    [
                        'User-Agent' => Constants::USER_AGENT_PREFIX . '_' . TestConstants::USER_AGENT_SUFFIX
                                        . '/' . TestConstants::USER_AGENT_VERSION,
                        'Authorization' => 'Bearer ' . TestConstants::TOKEN,
                    ],
                ]
            );
        $mockClient->getStreamDecisions();
    }

    public function testEnrollParams()
    {
        $mockFileStorage = $this->getFileStorageMock();
        $mockFileStorage->method('retrievePassword')->willReturn(
            TestConstants::PASSWORD
        );
        $mockFileStorage->method('retrieveMachineId')->willReturn(
            TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID
        );
        $mockFileStorage->method('retrieveToken')->willReturn(
            TestConstants::TOKEN
        );
        $mockFileStorage->method('retrieveScenarios')->willReturn(
            TestConstants::SCENARIOS
        );
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs, 'storage' => $mockFileStorage])
            ->onlyMethods(['request'])
            ->getMock();

        $testName = 'test-name';
        $testOverwrite = true;
        $testEnrollKey = 'test-enroll-id';
        $testTags = ['tag1', 'tag2'];
        $params = [
            'name' => $testName,
            'overwrite' => $testOverwrite,
            'attachment_key' => $testEnrollKey,
            'tags' => $testTags,
        ];
        $mockClient->expects($this->exactly(1))->method('request')
            ->withConsecutive(
                [
                    'POST',
                    Constants::ENROLL_ENDPOINT,
                    $params,
                    [
                        'User-Agent' => Constants::USER_AGENT_PREFIX . '_' . TestConstants::USER_AGENT_SUFFIX
                                        . '/' . TestConstants::USER_AGENT_VERSION,
                        'Authorization' => 'Bearer ' . TestConstants::TOKEN,
                    ],
                ]
            );
        $mockClient->enroll($testName, $testOverwrite, $testEnrollKey, $testTags);
    }

    public function testRequest()
    {
        // Test a valid POST request and its return
        $mockFileStorage = $this->getFileStorageMock();

        $mockCurl = $this->getCurlMock(['handle']);

        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                'configs' => $this->configs,
                'storage' => $mockFileStorage,
                'requestHandler' => $mockCurl,
            ])
            ->onlyMethods(['sendRequest'])
            ->getMock();

        $mockCurl->expects($this->exactly(1))->method('handle')->will($this->returnValue(
            new Response(MockedData::LOGIN_SUCCESS, MockedData::HTTP_200, [])
        ));

        $response = PHPUnitUtil::callMethod(
            $mockClient,
            'request',
            ['POST', '', [], []]
        );

        $this->assertEquals(
            json_decode(MockedData::LOGIN_SUCCESS, true),
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
        } catch (CommonClientException $e) {
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
        $client = new Watcher($this->configs, new FileStorage());

        $this->assertEquals(
            Constants::ENV_DEV,
            $client->getConfig('env'),
            'Env should be configured to dev by default'
        );
        $this->assertEquals(
            TestConstants::SCENARIOS,
            $client->getConfig('scenarios'),
            'Scenarios should be configured'
        );
        $this->assertEquals(
            TestConstants::MACHINE_ID_PREFIX,
            $client->getConfig('machine_id_prefix'),
            'Machine id prefix should be configured'
        );

        $this->assertEquals(
            TestConstants::USER_AGENT_SUFFIX,
            $client->getConfig('user_agent_suffix'),
            'User agent suffix should be configured'
        );

        $this->assertEquals(
            TestConstants::USER_AGENT_VERSION,
            $client->getConfig('user_agent_version'),
            'User agent version should be configured'
        );

        $this->assertEquals(
            TestConstants::API_TIMEOUT,
            $client->getConfig('api_timeout'),
            'Api timeout should be configured'
        );

        $client = new Watcher(['scenarios' => [TestConstants::SCENARIOS[0], TestConstants::SCENARIOS[0]]],
            new FileStorage()
        );

        $this->assertEquals(
            TestConstants::SCENARIOS,
            $client->getConfig('scenarios'),
            'Scenarios should be array unique'
        );

        // Test unexpected config
        $client = new Watcher(array_merge($this->configs, ['unexpected' => true]), new FileStorage());

        $this->assertEquals(
            Constants::ENV_DEV,
            $client->getConfig('env'),
            'Env should be configured to dev by default'
        );
        $this->assertEquals(
            null,
            $client->getConfig('unexpected'),
            'Unexpected config key should not be set with no thrown exception'
        );

        $client = new Watcher(['scenarios' => ['not-numeric-key' => TestConstants::SCENARIOS[0]]], new FileStorage());

        $this->assertEquals(
            TestConstants::SCENARIOS,
            $client->getConfig('scenarios'),
            'Scenarios should be indexed array'
        );

        $error = '';
        try {
            new Watcher([], new FileStorage());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/The child config "scenarios" under "watcherConfig" must be configured./',
            $error,
            'Scenarios key must be in configs'
        );

        $error = '';
        try {
            new Watcher(['scenarios' => []], new FileStorage());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/should have at least 1 element/',
            $error,
            'Scenarios should have at least 1 element'
        );

        $error = '';
        try {
            new Watcher(['scenarios' => ['']], new FileStorage());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/cannot contain an empty value/',
            $error,
            'Scenarios can not contain empty value'
        );

        $error = '';
        try {
            new Watcher(['scenarios' => ['test-bad-name']], new FileStorage());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Each scenario must match /',
            $error,
            'Each scenario respect some regex'
        );

        $error = '';
        try {
            new Watcher(['scenarios' => ['testtooloong/abcdefghijiklmnopqrstuvwxyzabcdefghijiklmnopqrstuvwxy']], new FileStorage());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Each scenario must match /',
            $error,
            'Each scenario respect some regex'
        );

        $error = '';
        try {
            new Watcher(['machine_id_prefix' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa'], new FileStorage());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Length must be <= 16/',
            $error,
            'machine_id_prefix length should be <16'
        );

        $error = '';
        try {
            new Watcher(['machine_id_prefix' => 'aaaaa  a'], new FileStorage());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Allowed chars are/',
            $error,
            'machine_id_prefix should contain allowed chars'
        );

        $client = new Watcher(['scenarios' => TestConstants::SCENARIOS, 'machine_id_prefix' => ''], new FileStorage());

        $this->assertEquals(
            '',
            $client->getConfig('machine_id_prefix'),
            'machine_id_prefix can be empty'
        );

        $this->assertTrue(
            Constants::API_TIMEOUT === (int) $client->getConfig('api_timeout'),
            'api timeout should be default'
        );

        $error = '';
        try {
            new Watcher(['user_agent_suffix' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa'], new FileStorage());
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
            new Watcher(['user_agent_suffix' => 'aaaaa  a'], new FileStorage());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Allowed chars are/',
            $error,
            'user_agent_suffix should contain allowed chars'
        );

        $client = new Watcher(['scenarios' => TestConstants::SCENARIOS, 'user_agent_suffix' => ''], new FileStorage());

        $this->assertEquals(
            '',
            $client->getConfig('user_agent_suffix'),
            'user_agent_suffix can be empty'
        );

        $error = '';
        try {
            new Watcher(['env' => 'preprod'], new FileStorage());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Permissible values:/',
            $error,
            'env should be dev or prod'
        );

        $client = new Watcher(['scenarios' => TestConstants::SCENARIOS, 'api_timeout' => 0], new FileStorage());
        $this->assertEquals(
            0,
            $client->getConfig('api_timeout'),
            'api timeout can be 0'
        );

        $client = new Watcher(['scenarios' => TestConstants::SCENARIOS, 'api_timeout' => -1], new FileStorage());
        $this->assertEquals(
            -1,
            $client->getConfig('api_timeout'),
            'api timeout can be negative'
        );

        $client = new Watcher(['scenarios' => TestConstants::SCENARIOS], new FileStorage());

        $this->assertEquals(
            Constants::VERSION,
            $client->getConfig('user_agent_version'),
            'user_agent_version should be lib version by default'
        );

        $client = new Watcher(['scenarios' => TestConstants::SCENARIOS, 'user_agent_version' => 'v4.56.7'], new FileStorage());

        $this->assertEquals(
            'v4.56.7',
            $client->getConfig('user_agent_version'),
            'user_agent_version should be configurable'
        );

        $error = '';
        try {
            new Watcher(['scenarios' => TestConstants::SCENARIOS, 'user_agent_version' => ''], new FileStorage());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Must match vX.Y.Z format/',
            $error,
            'User Agent version cannot be empty'
        );

        $error = '';
        try {
            new Watcher(['scenarios' => TestConstants::SCENARIOS, 'user_agent_version' => 'my-version'], new FileStorage());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Must match vX.Y.Z format/',
            $error,
            'User Agent version should match regex vX.Y.Z'
        );
    }

    public function testPrivateOrProtectedMethods()
    {
        $client = new Watcher($this->configs, new FileStorage());

        // Test areEquals
        $a = ['A', 'B'];
        $b = ['A', 'B'];

        $result = PHPUnitUtil::callMethod(
            $client,
            'areEquals',
            [$a, $b]
        );
        $this->assertEquals(
            true,
            $result,
            '$a and $b are equals'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'areEquals',
            [$b, $a]
        );
        $this->assertEquals(
            true,
            $result,
            '$b and $a are equals'
        );

        $a = ['B', 'A'];
        $b = ['A', 'B'];

        $result = PHPUnitUtil::callMethod(
            $client,
            'areEquals',
            [$a, $b]
        );
        $this->assertEquals(
            true,
            $result,
            '$a and $b are equals'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'areEquals',
            [$b, $a]
        );
        $this->assertEquals(
            true,
            $result,
            '$b and $a are equals'
        );

        $a = ['B', 'C'];
        $b = ['A', 'B'];

        $result = PHPUnitUtil::callMethod(
            $client,
            'areEquals',
            [$a, $b]
        );
        $this->assertEquals(
            false,
            $result,
            '$a and $b are different'
        );

        $a = ['A'];
        $b = ['A', 'B'];

        $result = PHPUnitUtil::callMethod(
            $client,
            'areEquals',
            [$a, $b]
        );
        $this->assertEquals(
            false,
            $result,
            '$a and $b are different'
        );

        $a = ['A', 'B'];
        $b = ['A'];

        $result = PHPUnitUtil::callMethod(
            $client,
            'areEquals',
            [$a, $b]
        );
        $this->assertEquals(
            false,
            $result,
            '$a and $b are different'
        );

        // Test generatePassword
        $result = PHPUnitUtil::callMethod(
            $client,
            'generatePassword',
            []
        );

        $this->assertEquals(
            Constants::PASSWORD_LENGTH,
            strlen($result),
            'Password should have right length'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/^[A-Za-z0-9]+$/',
            $result,
            'Password should be well formatted'
        );

        // Test generateMachineId
        $result = PHPUnitUtil::callMethod(
            $client,
            'generateMachineId',
            []
        );

        $this->assertEquals(
            Constants::MACHINE_ID_LENGTH,
            strlen($result),
            'Machine id should have right length'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/^[a-z0-9]+$/',
            $result,
            'Machine should be well formatted'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'generateMachineId',
            [['machine_id_prefix' => 'thisisatest']]
        );

        $this->assertEquals(
            Constants::MACHINE_ID_LENGTH,
            strlen($result),
            'Machine id should have right length'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/^[a-z0-9]+$/',
            $result,
            'Machine should be well formatted'
        );

        $this->assertEquals(
            'thisisatest',
            substr($result, 0, strlen('thisisatest')),
            'Machine id should begin with machine id prefix'
        );

        // Test  generateRandomString
        $error = '';
        try {
            PHPUnitUtil::callMethod(
                $client,
                'generateRandomString',
                [0, 'ab']
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Length must be greater than zero/',
            $error,
            'Random string must have a length greater than 0'
        );

        $error = '';
        try {
            PHPUnitUtil::callMethod(
                $client,
                'generateRandomString',
                [2, '']
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/There must be at least one allowed character./',
            $error,
            'There must be at least one allowed character.'
        );

        // Test shouldRefreshCredentials
        $result = PHPUnitUtil::callMethod(
            $client,
            'shouldRefreshCredentials',
            [null, 'test', []]
        );

        $this->assertEquals(
            true,
            $result,
            'Should refresh if no machine id'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'shouldRefreshCredentials',
            ['test', null, []]
        );

        $this->assertEquals(
            true,
            $result,
            'Should refresh if no password'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'shouldRefreshCredentials',
            ['test-machine-id', 'test-password', []]
        );

        $this->assertEquals(
            false,
            $result,
            'Should not refresh'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'shouldRefreshCredentials',
            ['test-machine-id', 'test-password', ['machine_id_prefix' => 'test-prefix']]
        );

        $this->assertEquals(
            true,
            $result,
            'Should refresh if machine id prefix differs from machine id start'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'shouldRefreshCredentials',
            ['test-machine-id', 'test-password', ['machine_id_prefix' => 'test-ma']]
        );

        $this->assertEquals(
            false,
            $result,
            'Should not refresh if machine id starts with machine id prefix'
        );

        // Test handleLogin errors
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::LOGIN_BAD_CREDENTIALS,
                MockedData::LOGIN_SUCCESS
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(MockedData::HTTP_200, MockedData::HTTP_200
            )
        );
        $client = new Watcher($this->configs, $mockFileStorage, $mockCurlRequest);

        $error = '';
        try {
            PHPUnitUtil::callMethod(
                $client,
                'handleLogin',
                []
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/required token/',
            $error,
            'Empty token should throw an error'
        );

        // Test refresh credentials
        $root = vfsStream::setup('/tmp');
        $storage = new FileStorage($root->url());
        $client = new Watcher($this->configs, $storage);

        $this->assertEquals(
            false,
            file_exists($root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::MACHINE_ID_FILE),
            'File should not exist'
        );

        $this->assertEquals(
            false,
            file_exists($root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::PASSWORD_FILE),
            'File should not exist'
        );
        PHPUnitUtil::callMethod(
            $client,
            'refreshCredentials',
            []
        );

        $this->assertEquals(
            true,
            file_exists($root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::MACHINE_ID_FILE),
            'File should exist'
        );

        $this->assertEquals(
            true,
            file_exists($root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::PASSWORD_FILE),
            'File should exist'
        );

        $password = $storage->retrievePassword();

        PHPUnitUtil::assertRegExp(
            $this,
            '/^[A-Za-z0-9]+$/',
            $password,
            'Password should be well formatted'
        );

        $machineId = $storage->retrieveMachineId();

        $this->assertEquals(
            Constants::MACHINE_ID_LENGTH,
            strlen($machineId),
            'Machine id should have right length'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/^[A-Za-z0-9]+$/',
            $machineId,
            'Machine should be well formatted'
        );

        $this->assertEquals(
            TestConstants::MACHINE_ID_PREFIX,
            substr($machineId, 0, strlen(TestConstants::MACHINE_ID_PREFIX)),
            'Machine id should begin with machine id prefix'
        );

        // Test normalizeTags
        $tags = ['tag1', 'tag2', 'tag3'];
        $result = PHPUnitUtil::callMethod(
            $client,
            'normalizeTags',
            [$tags]
        );
        $this->assertEquals(
            $tags,
            $result,
            'Right tags should be unchanged'
        );

        $tags = ['tag1', 'tag1', 'tag3'];
        $result = PHPUnitUtil::callMethod(
            $client,
            'normalizeTags',
            [$tags]
        );
        $this->assertEquals(
            [],
            array_diff($result, ['tag1', 'tag3']),
            'Tags should be unique'
        );

        $tags = ['a' => 'tag1'];
        $result = PHPUnitUtil::callMethod(
            $client,
            'normalizeTags',
            [$tags]
        );
        $this->assertEquals(
            ['tag1'],
            $result,
            'Tags should be indexed array'
        );

        $error = '';
        $tags = ['tag1', ['tag2']];
        try {
            PHPUnitUtil::callMethod(
                $client,
                'normalizeTags',
                [$tags]
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Tag must be a string: array given/',
            $error,
            'Should throw an error if tag is not a string'
        );

        $error = '';
        $tags = ['tag1', '', 'tag3'];
        try {
            PHPUnitUtil::callMethod(
                $client,
                'normalizeTags',
                [$tags]
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Tag must not be empty/',
            $error,
            'Should throw an error if tag is empty'
        );

        // Test convertSecondsToDuration

        $result = PHPUnitUtil::callMethod(
            $client,
            'convertSecondsToDuration',
            [86400]
        );
        $this->assertEquals(
            '24h0m0s',
            $result,
            '86400s Should be 24h0m0s'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'convertSecondsToDuration',
            [90]
        );
        $this->assertEquals(
            '0h1m30s',
            $result,
            '90s Should be 0h1m30s'
        );
    }

    private function getTestSignal(string $machineId): string
    {
        return '{"scenario":"' . TestConstants::SCENARIOS[0] . '","scenario_hash":"","scenario_version":"","created_at":"XXX","machine_id":"' . $machineId . '","message":"","start_at":"XXX","stop_at":"XXX","scenario_trust":"manual","decisions":[{"id":0,"duration":"24h0m0s","scenario":"' . TestConstants::SCENARIOS[0] . '","origin":"' . Constants::ORIGIN . '","scope":"' . Constants::SCOPE_IP . '","value":"1.2.3.4","type":"' . Constants::REMEDIATION_BAN . '","simulated":false}],"source":{"scope":"' . Constants::SCOPE_IP . '","value":"1.2.3.4"}}
';
    }

    public function testBuildSimpleSignal()
    {
        $mockFileStorage = $this->getFileStorageMock();
        $machineId = TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID;

        $mockFileStorage->method('retrieveMachineId')->will(
            $this->onConsecutiveCalls(
                $machineId, // Test 1 : machine id is already in storage
                null, // Test 2 : machine id is not in storage
                $machineId . 'test2', // Test 2 : machine id is now in storage (freshly created)
                $machineId . 'test2', // Test 2 : machine id is now in storage
                $machineId . 'test3', // Test 3 : machine id is already in storage
                $machineId . 'test4' // Test 4 : machine id is already in storage
            )
        );

        $mockFileStorage->method('retrievePassword')->will(
            $this->onConsecutiveCalls(
                TestConstants::PASSWORD // Test 2 : machine id is not already in storage
            )
        );

        $client = new Watcher($this->configs, $mockFileStorage);

        $currentTime = new \DateTime('now', new \DateTimeZone('UTC'));
        // Test 1
        $signal = $client->buildSimpleSignalForIp('1.2.3.4', TestConstants::SCENARIOS[0], null);
        $signalCreated = new \DateTime($signal['created_at']);
        $signalCreatedTimestamp = $signalCreated->getTimestamp();
        $signalStart = new \DateTime($signal['start_at']);
        $signalStartTimestamp = $signalStart->getTimestamp();
        $signalStop = new \DateTime($signal['stop_at']);
        $signalStopTimestamp = $signalStop->getTimestamp();

        PHPUnitUtil::assertRegExp(
            $this,
            '#^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(.\d{6})?Z$#',
            $signal['created_at'],
            'created_at should be well formatted'
        );
        PHPUnitUtil::assertRegExp(
            $this,
            '#^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(.\d{6})?Z$#',
            $signal['stop_at'],
            'stop_at should be well formatted'
        );
        PHPUnitUtil::assertRegExp(
            $this,
            '#^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(.\d{6})?Z$#',
            $signal['start_at'],
            'start_at should be well formatted'
        );
        // Test Only non date field (hard to test with milliseconds)
        $signal['created_at'] = 'XXX';
        $signal['start_at'] = 'XXX';
        $signal['stop_at'] = 'XXX';

        $this->assertEquals(
            $signal,
            json_decode($this->getTestSignal($machineId), true),
            'Signal should be well formatted'
        );

        $this->assertEquals(
            $signalCreatedTimestamp,
            $currentTime->getTimestamp(),
            'Signal created_at should be current time'
        );
        $this->assertEquals(
            $signalStartTimestamp,
            $currentTime->getTimestamp(),
            'Signal start_at should be current time'
        );
        $this->assertEquals(
            $signalStopTimestamp,
            $currentTime->getTimestamp(),
            'Signal stop_at should be current time'
        );

        $currentTime = new \DateTime('now', new \DateTimeZone('UTC'));
        // Test 2
        $signal = $client->buildSimpleSignalForIp('1.2.3.4', TestConstants::SCENARIOS[0], null);
        $signalCreated = new \DateTime($signal['created_at']);
        $signalCreatedTimestamp = $signalCreated->getTimestamp();

        PHPUnitUtil::assertRegExp(
            $this,
            '#^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(.\d{6})?Z$#',
            $signal['created_at'],
            'created_at should be well formatted'
        );
        PHPUnitUtil::assertRegExp(
            $this,
            '#^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(.\d{6})?Z$#',
            $signal['stop_at'],
            'stop_at should be well formatted'
        );
        PHPUnitUtil::assertRegExp(
            $this,
            '#^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(.\d{6})?Z$#',
            $signal['start_at'],
            'start_at should be well formatted'
        );
        // Test Only non date field (hard to test with milliseconds)
        $signal['created_at'] = 'XXX';
        $signal['start_at'] = 'XXX';
        $signal['stop_at'] = 'XXX';

        $this->assertEquals(
            $signal,
            json_decode($this->getTestSignal($machineId . 'test2'), true),
            'Signal should be well formatted if watcher not registered at first'
        );

        $this->assertEquals(
            $signalCreatedTimestamp,
            $currentTime->getTimestamp(),
            'Signal created_at should be current time'
        );

        // Test 3
        $startTime = new \DateTime('1979-03-06 10:55:28');
        $signal = $client->buildSimpleSignalForIp('1.2.3.4', TestConstants::SCENARIOS[0], $startTime);
        $signalCreated = new \DateTime($signal['created_at']);
        $signalCreatedTimestamp = $signalCreated->getTimestamp();
        $signalStart = new \DateTime($signal['start_at']);
        $signalStartTimestamp = $signalStart->getTimestamp();

        $this->assertEquals(
            $signalCreatedTimestamp,
            $startTime->getTimestamp(),
            'Signal created_at should be configured time'
        );
        $this->assertEquals(
            $signalStartTimestamp,
            $startTime->getTimestamp(),
            'Signal start_at should be configured time'
        );
        // Test Only non date field (hard to test with milliseconds)
        $signal['created_at'] = 'XXX';
        $signal['start_at'] = 'XXX';
        $signal['stop_at'] = 'XXX';
        $this->assertEquals(
            $signal,
            json_decode($this->getTestSignal($machineId . 'test3'), true),
            'Signal should be well formatted if watcher not registered at first'
        );

        // Test 4 : errors
        $error = '';
        try {
            $client->buildSimpleSignalForIp('1.2.3.4', 'hello-world-bad-scenario', null);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Invalid scenario/',
            $error,
            'Should throw an error for bad scenario'
        );
    }

    public function testBuildSignal()
    {
        $mockFileStorage = $this->getFileStorageMock();
        $machineId = TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID;

        $mockFileStorage->method('retrieveMachineId')->will(
            $this->onConsecutiveCalls(
                $machineId . 'test1', // Test 1 : machine id is already in storage
                $machineId . 'test3', // Test 3 : machine id is already in storage (Test 2 throws error before)
                $machineId . 'test4', // Test 4 : machine id is already in storage
                $machineId . 'test5', // Test 5 : machine id is already in storage,
                $machineId . 'test6', // Test 6 : machine id is already in storage
                $machineId . 'test7' // Test 7 : machine id is already in storage
            )
        );

        $client = new Watcher($this->configs, $mockFileStorage);

        // Test 1 : can build a signal with custom values
        $properties = [
            'scenario' => TestConstants::SCENARIOS[0],
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
                'origin' => 'crowdsec-unit-test',
                'scope' => $sourceScope,
                'value' => $sourceValue,
                'type' => 'custom',
                'simulated' => true,
            ],
        ];

        $signal = $client->buildSignal($properties, $source, $decisions);
        $expected = json_decode('{"scenario":"test\/scenario","scenario_hash":"azertyuiop","scenario_version":"v1.2.0","scenario_trust":"certified","created_at":"2023-01-13T01:34:56.778054Z","machine_id":"capiclienttesttest-machine-idtest1","message":"This is a test message","start_at":"2023-01-12T23:48:45.123456Z","stop_at":"2022-01-13T01:34:55.432150Z","decisions":[{"id":1979,"duration":"1h0m0s","scenario":"test\/scenario","origin":"crowdsec-unit-test","scope":"ip","value":"' . TestConstants::IP . '","type":"custom","simulated":true}],"source":{"scope":"ip","value":"' . TestConstants::IP . '"}}', true);

        $this->assertEquals(
            $expected, $signal,
            'Signal should be well formatted'
        );

        // Test 2 : error with date
        $properties = [
            'scenario' => TestConstants::SCENARIOS[0],
            'scenario_trust' => 'certified',
            'scenario_version' => 'v1.2.0',
            'scenario_hash' => 'azertyuiop',
            'created_at' => '2023-01-13',
            'message' => 'This is a test message',
            'start_at' => new \DateTime('2023-01-12T23:48:45.123456Z'),
            'stop_at' => new \DateTime('2022-01-13T01:34:55.432150Z'),
        ];

        $sourceScope = Constants::SCOPE_IP;
        $sourceValue = '1.2.3.4';

        $source = [
            'scope' => $sourceScope,
            'value' => $sourceValue,
        ];

        $decisions = [
            [
                'id' => 1979,
                'duration' => 3600,
                'origin' => 'crowdsec-unit-test',
                'scope' => $sourceScope,
                'value' => $sourceValue,
                'type' => 'custom',
                'simulated' => true,
            ],
        ];
        $error = '';
        try {
            $client->buildSignal($properties, $source, $decisions);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Date input must be null or implement DateTimeInterface/',
            $error,
            'Should throw an error for bad created_at'
        );
        // Test 3 : non integer duration throws an exception
        $properties = [
            'scenario' => TestConstants::SCENARIOS[0],
            'scenario_trust' => 'certified',
            'scenario_version' => 'v1.2.0',
            'scenario_hash' => 'azertyuiop',
            'created_at' => new \DateTime('2023-01-13T01:34:56.778054Z'),
            'message' => 'This is a test message',
            'start_at' => new \DateTime('2023-01-12T23:48:45.123456Z'),
            'stop_at' => new \DateTime('2022-01-13T01:34:55.432150Z'),
        ];

        $sourceScope = Constants::SCOPE_IP;
        $sourceValue = '1.2.3.4';

        $source = [
            'scope' => $sourceScope,
            'value' => $sourceValue,
        ];

        $decisions = [
            [
                'id' => 1979,
                'duration' => '24h53m',
                'origin' => 'crowdsec-unit-test',
                'scope' => $sourceScope,
                'value' => $sourceValue,
                'type' => 'custom',
                'simulated' => true,
            ],
        ];

        $error = '';
        try {
            $client->buildSignal($properties, $source, $decisions);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Decision duration must be an integer/',
            $error,
            'Should throw an error for non integer duration'
        );
        // Test 4 : non array decision

        $sourceScope = Constants::SCOPE_IP;
        $sourceValue = '1.2.3.4';

        $source = [
            'scope' => $sourceScope,
            'value' => $sourceValue,
        ];

        $decisions = [
            'this-is-not-an-array',
        ];

        $error = '';
        try {
            $client->buildSignal($properties, $source, $decisions);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Decision must be an array/',
            $error,
            'Should throw an error if no scenario'
        );

        // Test 5 : no scenario
        $properties = [
            'scenario' => '',
            'scenario_trust' => 'certified',
            'scenario_version' => 'v1.2.0',
            'scenario_hash' => 'azertyuiop',
            'created_at' => new \DateTime('2023-01-13T01:34:56.778054Z'),
            'message' => 'This is a test message',
            'start_at' => new \DateTime('2023-01-12T23:48:45.123456Z'),
            'stop_at' => new \DateTime('2022-01-13T01:34:55.432150Z'),
        ];

        $sourceScope = Constants::SCOPE_IP;
        $sourceValue = '1.2.3.4';

        $source = [
            'scope' => $sourceScope,
            'value' => $sourceValue,
        ];

        $decisions = [
            [
                'id' => 1979,
                'duration' => 3600,
                'origin' => 'crowdsec-unit-test',
                'scope' => $sourceScope,
                'value' => $sourceValue,
                'type' => 'custom',
                'simulated' => true,
            ],
        ];

        $error = '';
        try {
            $client->buildSignal($properties, $source, $decisions);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/The path "signalConfig.scenario" cannot contain an empty value/',
            $error,
            'Should throw an error for non array decision'
        );
        // Test 6 : Empty decisions OK
        $properties = [
            'scenario' => TestConstants::SCENARIOS[0],
            'scenario_trust' => 'certified',
            'scenario_version' => 'v1.2.0',
            'scenario_hash' => 'azertyuiop',
            'created_at' => new \DateTime('2023-01-13T01:34:56.778054Z'),
            'message' => 'This is a test message',
            'start_at' => new \DateTime('2023-01-12T23:48:45.123456Z'),
            'stop_at' => new \DateTime('2022-01-13T01:34:55.432150Z'),
        ];

        $sourceScope = Constants::SCOPE_IP;
        $sourceValue = '1.2.3.4';

        $source = [
            'scope' => $sourceScope,
            'value' => $sourceValue,
        ];

        $decisions = [];

        $signal = $client->buildSignal($properties, $source, $decisions);

        $this->assertEquals([], $signal['decisions'], 'Should be able to send empty decisions array');
        // Test 7 start and stop should be populated with created at if not set
        $properties = [
            'scenario' => TestConstants::SCENARIOS[0],
            'scenario_trust' => 'certified',
            'scenario_version' => 'v1.2.0',
            'scenario_hash' => 'azertyuiop',
            'created_at' => new \DateTime('2023-01-13T01:34:56.778054Z'),
            'message' => 'This is a test message',
        ];

        $sourceScope = Constants::SCOPE_IP;
        $sourceValue = '1.2.3.4';

        $source = [
            'scope' => $sourceScope,
            'value' => $sourceValue,
        ];

        $decisions = [
            [
                'id' => 1979,
                'duration' => 3600,
                'origin' => 'crowdsec-unit-test',
                'scope' => $sourceScope,
                'value' => $sourceValue,
                'type' => 'custom',
                'simulated' => true,
            ],
        ];

        $signal = $client->buildSignal($properties, $source, $decisions);
        $expected = json_decode('{"scenario":"test\/scenario","scenario_hash":"azertyuiop","scenario_version":"v1.2.0","scenario_trust":"certified","created_at":"2023-01-13T01:34:56.778054Z","machine_id":"capiclienttesttest-machine-idtest7","message":"This is a test message","start_at":"2023-01-13T01:34:56.778054Z","stop_at":"2023-01-13T01:34:56.778054Z","decisions":[{"id":1979,"duration":"1h0m0s","scenario":"test\/scenario","origin":"crowdsec-unit-test","scope":"ip","value":"1.2.3.4","type":"custom","simulated":true}],"source":{"scope":"ip","value":"1.2.3.4"}}', true);

        $this->assertEquals(
            $expected, $signal,
            'start_at and stop_at should be populated with created at if not set'
        );
    }
}
