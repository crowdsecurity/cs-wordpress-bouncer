<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Tests\Unit;

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

use CrowdSec\CapiClient\ClientException;
use CrowdSec\CapiClient\Constants;
use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Tests\Constants as TestConstants;
use CrowdSec\CapiClient\Tests\MockedData;
use CrowdSec\CapiClient\Tests\PHPUnitUtil;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Client\RequestHandler\Curl;

/**
 * @uses \CrowdSec\CapiClient\Configuration\Watcher::getConfigTreeBuilder
 * @uses \CrowdSec\CapiClient\Watcher::__construct
 * @uses \CrowdSec\CapiClient\Watcher::configure
 * @uses \CrowdSec\CapiClient\Watcher::formatUserAgent
 * @uses \CrowdSec\CapiClient\Watcher::ensureAuth
 * @uses \CrowdSec\CapiClient\Watcher::ensureRegister
 * @uses \CrowdSec\CapiClient\Watcher::shouldRefreshCredentials
 * @uses \CrowdSec\CapiClient\Watcher::generateMachineId
 * @uses \CrowdSec\CapiClient\Watcher::generatePassword
 * @uses \CrowdSec\CapiClient\Watcher::generateRandomString
 * @uses \CrowdSec\CapiClient\Watcher::refreshCredentials
 * @uses \CrowdSec\CapiClient\Watcher::areEquals
 * @uses \CrowdSec\CapiClient\Storage\FileStorage::__construct
 *
 * @covers \CrowdSec\CapiClient\Watcher::login
 * @covers \CrowdSec\CapiClient\Watcher::handleTokenHeader
 * @covers \CrowdSec\CapiClient\Watcher::getStreamDecisions
 * @covers \CrowdSec\CapiClient\Watcher::register
 * @covers \CrowdSec\CapiClient\Watcher::login
 * @covers \CrowdSec\CapiClient\Watcher::shouldLogin
 * @covers \CrowdSec\CapiClient\Watcher::handleLogin
 * @covers \CrowdSec\CapiClient\Watcher::pushSignals
 * @covers \CrowdSec\CapiClient\Watcher::manageRequest
 */
final class CurlTest extends AbstractClient
{
    public function testDecisionsStream()
    {
        // Success test
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->willReturn(
            MockedData::DECISIONS_STREAM_LIST
        );
        $mockCurlRequest->method('getResponseHttpCode')->willReturn(
            MockedData::HTTP_200
        );
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
        $client = new Watcher($this->configs, $mockFileStorage, $mockCurlRequest);
        $decisionsResponse = $client->getStreamDecisions();

        $this->assertEquals(
            json_decode(MockedData::DECISIONS_STREAM_LIST, true),
            $decisionsResponse,
            'Success get decisions stream'
        );
    }

    public function testLogin()
    {
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::LOGIN_SUCCESS,
                MockedData::LOGIN_BAD_CREDENTIALS,
                MockedData::BAD_REQUEST
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(MockedData::HTTP_200, MockedData::HTTP_403, MockedData::HTTP_400)
        );
        $client = new Watcher($this->configs, $mockFileStorage, $mockCurlRequest);

        $loginResponse = PHPUnitUtil::callMethod(
            $client,
            'login',
            []
        );
        // 200
        $this->assertEquals(
            'this-is-a-token',
            $loginResponse['token'],
            'Success login case'
        );
        // 403
        $error = '';
        try {
            PHPUnitUtil::callMethod(
                $client,
                'login',
                []
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_403 . '.*The machine_id or password is incorrect/',
            $error,
            'Bad credential login case'
        );

        // 400
        $error = '';
        try {
            PHPUnitUtil::callMethod(
                $client,
                'login',
                []
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }
        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_400 . '.*Invalid request body/',
            $error,
            'Bad request login case'
        );
    }

    public function testRefreshToken()
    {
        // Test refresh with good credential
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->willReturn(
            MockedData::LOGIN_SUCCESS
        );
        $mockCurlRequest->method('getResponseHttpCode')->willReturn(MockedData::HTTP_200);
        $mockFileStorage->method('retrievePassword')->willReturn(
            TestConstants::PASSWORD
        );
        $mockFileStorage->method('retrieveMachineId')->willReturn(TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID);
        $mockFileStorage->method('retrieveToken')->willReturn(null);
        $client = new Watcher($this->configs, $mockFileStorage, $mockCurlRequest);
        PHPUnitUtil::callMethod(
            $client,
            'ensureAuth',
            []
        );
        $tokenHeader = PHPUnitUtil::callMethod(
            $client,
            'handleTokenHeader',
            []
        );

        $this->assertEquals(
            'Bearer this-is-a-token',
            $tokenHeader['Authorization'],
            'Header should be populated with token'
        );
        // Test refresh with bad credential
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::LOGIN_BAD_CREDENTIALS
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->willReturn(MockedData::HTTP_400);
        $mockFileStorage->method('retrievePassword')->willReturn(TestConstants::PASSWORD);
        $mockFileStorage->method('retrieveMachineId')->willReturn(TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID);
        $mockFileStorage->method('retrieveToken')->willReturn(null);
        $client = new Watcher($this->configs, new FileStorage(), $mockCurlRequest);

        $error = '';
        $code = 0;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'handleTokenHeader',
                []
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
        }

        $this->assertEquals(401, $code);

        PHPUnitUtil::assertRegExp(
            $this,
            '/Token is required/',
            $error,
            'No retrieved token should throw a ClientException error'
        );
    }

    public function testRegister()
    {
        // All tests are based on register retry attempts value
        $this->assertEquals(Constants::REGISTER_RETRY, 1);
        // 500 (successive attempts)
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::REGISTER_ALREADY,
                MockedData::REGISTER_ALREADY
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(
                MockedData::HTTP_500,
                MockedData::HTTP_500
            )
        );

        $client = new Watcher($this->configs, $mockFileStorage, $mockCurlRequest);
        $error = '';
        try {
            PHPUnitUtil::callMethod(
                $client,
                'register',
                []
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }
        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_500 . '.*User already registered/',
            $error,
            'Already registered case'
        );
        // 200 (first attempt)
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::SUCCESS
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(
                MockedData::HTTP_200
            )
        );

        $client = new Watcher($this->configs, $mockFileStorage, $mockCurlRequest);
        $error = 'none';
        try {
            PHPUnitUtil::callMethod(
                $client,
                'register',
                []
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }
        PHPUnitUtil::assertRegExp(
            $this,
            '/none/',
            $error,
            'Success case'
        );
        // 400 (successive attempts)
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::BAD_REQUEST,
                MockedData::BAD_REQUEST
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(
                MockedData::HTTP_400,
                MockedData::HTTP_400
            )
        );

        $client = new Watcher($this->configs, $mockFileStorage, $mockCurlRequest);
        $error = '';
        try {
            PHPUnitUtil::callMethod(
                $client,
                'register',
                []
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }
        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_400 . '.*Invalid request body/',
            $error,
            'Bad request registered case'
        );
        // 200 (after 1 failed attempt)
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::REGISTER_ALREADY,
                MockedData::SUCCESS
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(
                MockedData::HTTP_500,
                MockedData::HTTP_200
            )
        );

        $client = new Watcher($this->configs, $mockFileStorage, $mockCurlRequest);
        $error = 'none';
        try {
            PHPUnitUtil::callMethod(
                $client,
                'register',
                []
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }
        PHPUnitUtil::assertRegExp(
            $this,
            '/none/',
            $error,
            'Success case'
        );
    }

    public function testSignals()
    {
        // Success test
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::SUCCESS
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(MockedData::HTTP_200)
        );
        $mockFileStorage->method('retrievePassword')->will(
            $this->onConsecutiveCalls(
                TestConstants::PASSWORD
            )
        );
        $mockFileStorage->method('retrieveMachineId')->will(
            $this->onConsecutiveCalls(
                TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID
            )
        );
        $mockFileStorage->method('retrieveToken')->will(
            $this->onConsecutiveCalls(
                TestConstants::TOKEN
            )
        );
        $mockFileStorage->method('retrieveScenarios')->willReturn(
            TestConstants::SCENARIOS
        );
        $client = new Watcher($this->configs, $mockFileStorage, $mockCurlRequest);

        $signalsResponse = $client->pushSignals([]);

        $this->assertEquals(
            'OK',
            $signalsResponse['message'],
            'Success pushed signals'
        );
        // Failed test
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::SIGNALS_BAD_REQUEST
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(MockedData::HTTP_400)
        );
        $mockFileStorage->method('retrievePassword')->will(
            $this->onConsecutiveCalls(
                TestConstants::PASSWORD
            )
        );
        $mockFileStorage->method('retrieveMachineId')->will(
            $this->onConsecutiveCalls(
                TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID
            )
        );
        $mockFileStorage->method('retrieveToken')->will(
            $this->onConsecutiveCalls(
                TestConstants::TOKEN
            )
        );
        $client = new Watcher($this->configs, $mockFileStorage, $mockCurlRequest);
        $error = '';
        $code = 0;
        try {
            $client->pushSignals([]);
        } catch (ClientException $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*Invalid request body.*scenario_hash/',
            $error,
            'Bad signals request'
        );
        $this->assertEquals(MockedData::HTTP_400, $code);

        // Failed test with error not 401
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::SUCCESS
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(MockedData::HTTP_500)
        );
        $mockFileStorage->method('retrievePassword')->will(
            $this->onConsecutiveCalls(
                TestConstants::PASSWORD
            )
        );
        $mockFileStorage->method('retrieveMachineId')->will(
            $this->onConsecutiveCalls(
                TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID
            )
        );
        $mockFileStorage->method('retrieveToken')->will(
            $this->onConsecutiveCalls(
                TestConstants::TOKEN
            )
        );
        $mockFileStorage->method('retrieveScenarios')->willReturn(
            TestConstants::SCENARIOS
        );
        $client = new Watcher($this->configs, $mockFileStorage, $mockCurlRequest);

        $code = 0;
        try {
            $client->pushSignals([]);
        } catch (ClientException $e) {
            $code = $e->getCode();
        }

        $this->assertEquals(MockedData::HTTP_500, $code, 'Should throw an error if not 401');

        // Failed test with multiple error 401
        $mockCurlRequest = $this->getCurlMock();
        $mockFileStorage = $this->getFileStorageMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::LOGIN_BAD_CREDENTIALS, MockedData::LOGIN_BAD_CREDENTIALS
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(MockedData::HTTP_401, MockedData::HTTP_401)
        );
        $mockFileStorage->method('retrievePassword')->will(
            $this->onConsecutiveCalls(
                TestConstants::PASSWORD, TestConstants::PASSWORD
            )
        );
        $mockFileStorage->method('retrieveMachineId')->will(
            $this->onConsecutiveCalls(
                TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID,
                TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID
            )
        );
        $mockFileStorage->method('retrieveToken')->will(
            $this->onConsecutiveCalls(
                TestConstants::TOKEN, TestConstants::TOKEN
            )
        );
        $mockFileStorage->method('retrieveScenarios')->willReturn(
            TestConstants::SCENARIOS, TestConstants::SCENARIOS
        );
        $client = new Watcher($this->configs, $mockFileStorage, $mockCurlRequest);

        $error = '';
        try {
            $client->pushSignals([]);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Could not login after ' . (Constants::LOGIN_RETRY + 1) . ' attempts/',
            $error,
            'Should throw error after multiple attempts'
        );
    }
}
