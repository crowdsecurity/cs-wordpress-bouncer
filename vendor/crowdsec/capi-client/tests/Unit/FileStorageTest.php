<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Tests\Unit;

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

use CrowdSec\CapiClient\Constants;
use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Tests\PHPUnitUtil;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::__construct
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::getBasePath
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::storePassword
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::writeFile
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::readFile
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::retrievePassword
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::retrieveToken
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::retrieveScenarios
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::retrieveMachineId
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::storeToken
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::storeMachineId
 * @covers \CrowdSec\CapiClient\Storage\FileStorage::storeScenarios
 */
final class FileStorageTest extends TestCase
{
    public const TMP_DIR = '/tmp';
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * set up test environment.
     */
    public function setUp(): void
    {
        $this->root = vfsStream::setup(self::TMP_DIR);
    }

    public function testRetrieveMachineId()
    {
        $storage = new FileStorage($this->root->url());
        // Test no file
        $this->assertEquals(
            null,
            $storage->retrieveMachineId(),
            'Should be null if no file on file system'
        );
        // test file ok
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::MACHINE_ID_FILE, 0444)
            ->at($this->root)
            ->setContent('{"machine_id":"test-machine-id"}');
        $this->assertEquals(
            'test-machine-id',
            $storage->retrieveMachineId(),
            'Should be ok if file is present with right content and permission'
        );
        // Test file not readable
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::MACHINE_ID_FILE, 0000)
            ->at($this->root)
            ->setContent('{"machine_id":"test-machine-id"}');
        $this->assertEquals(
            null,
            $storage->retrieveMachineId(),
            'Should be null if not readable'
        );
        // Test file bad content
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::MACHINE_ID_FILE, 0000)
            ->at($this->root)
            ->setContent('{"foo":"test-machine-id"}');
        $this->assertEquals(
            null,
            $storage->retrieveMachineId(),
            'Should be null if bad content'
        );
    }

    public function testRetrieveScenarios()
    {
        $storage = new FileStorage($this->root->url());
        // Test no file
        $this->assertEquals(
            null,
            $storage->retrieveScenarios(),
            'Should be null if no file on file system'
        );
        // test file ok
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::SCENARIOS_FILE, 0444)
            ->at($this->root)
            ->setContent('{"scenarios":["crowdsecurity\/http-backdoors-attempts"]}');
        $this->assertEquals(
            ['crowdsecurity/http-backdoors-attempts'],
            $storage->retrieveScenarios(),
            'Should be ok if file is present with right content and permission'
        );
        // Test file not readable
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::SCENARIOS_FILE, 0000)
            ->at($this->root)
            ->setContent('{"scenarios":["crowdsecurity\/http-backdoors-attempts"]}');
        $this->assertEquals(
            null,
            $storage->retrieveScenarios(),
            'Should be null if not readable'
        );
        // Test file bad content
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::SCENARIOS_FILE, 0000)
            ->at($this->root)
            ->setContent('{"foo":["crowdsecurity\/http-backdoors-attempts"]}');
        $this->assertEquals(
            null,
            $storage->retrieveScenarios(),
            'Should be null if bad content'
        );
    }

    public function testRetrievePassword()
    {
        $storage = new FileStorage($this->root->url());
        // Test no file
        $this->assertEquals(
            null,
            $storage->retrievePassword(),
            'Should be null if no file on file system'
        );
        // test file ok
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::PASSWORD_FILE, 0444)
            ->at($this->root)
            ->setContent('{"password":"test-password"}');
        $this->assertEquals(
            'test-password',
            $storage->retrievePassword(),
            'Should be ok if file is present with right content and permission'
        );
        // Test file not readable
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::PASSWORD_FILE, 0000)
            ->at($this->root)
            ->setContent('{"password":"test-password"}');
        $this->assertEquals(
            null,
            $storage->retrievePassword(),
            'Should be null if not readable'
        );
        // Test file bad content
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::PASSWORD_FILE, 0000)
            ->at($this->root)
            ->setContent('{"foo":"test-password"}');
        $this->assertEquals(
            null,
            $storage->retrievePassword(),
            'Should be null if bad content'
        );
    }

    public function testRetrieveToken()
    {
        $storage = new FileStorage($this->root->url());
        // Test no file
        $this->assertEquals(
            null,
            $storage->retrieveToken(),
            'Should be null if no file on file system'
        );
        // test file ok
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::TOKEN_FILE, 0444)
            ->at($this->root)
            ->setContent('{"token":"test-token"}');
        $this->assertEquals(
            'test-token',
            $storage->retrieveToken(),
            'Should be ok if file is present with right content and permission'
        );
        // Test file not readable
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::TOKEN_FILE, 0000)
            ->at($this->root)
            ->setContent('{"token":"test-token"}');
        $this->assertEquals(
            null,
            $storage->retrieveToken(),
            'Should be null if not readable'
        );
        // Test file bad content
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::TOKEN_FILE, 0000)
            ->at($this->root)
            ->setContent('{"foo":"test-token"}');
        $this->assertEquals(
            null,
            $storage->retrieveToken(),
            'Should be null if bad content'
        );
    }

    public function testStoreMachineId()
    {
        $storage = new FileStorage($this->root->url());

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::MACHINE_ID_FILE),
            'File should not exist'
        );

        $storage->storeMachineId('test-machine-id');

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::MACHINE_ID_FILE),
            'Should create file'
        );

        $this->assertEquals(
            '{"machine_id":"test-machine-id"}',
            file_get_contents($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::MACHINE_ID_FILE),
            'Should have right content'
        );

        // Test with not writable file
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::MACHINE_ID_FILE, 0444)
            ->at($this->root);

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::MACHINE_ID_FILE),
            'Should create file'
        );

        $result = $storage->storeMachineId('test-machine-id');
        $this->assertEquals(
            false,
            $result,
            'Should return false on error'
        );
    }

    public function testStorePassword()
    {
        $storage = new FileStorage($this->root->url());

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::PASSWORD_FILE),
            'File should not exist'
        );

        $storage->storePassword('test-pwd');

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::PASSWORD_FILE),
            'Should create file'
        );

        $this->assertEquals(
            '{"password":"test-pwd"}',
            file_get_contents($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::PASSWORD_FILE),
            'Should have right content'
        );

        // Test with not writable file
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::PASSWORD_FILE, 0444)
            ->at($this->root);

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::PASSWORD_FILE),
            'Should create file'
        );

        $result = $storage->storePassword('test-password');
        $this->assertEquals(
            false,
            $result,
            'Should return false on error'
        );
    }

    public function testStoreToken()
    {
        $storage = new FileStorage($this->root->url());

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::TOKEN_FILE),
            'File should not exist'
        );

        $storage->storeToken('test-token');

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::TOKEN_FILE),
            'Should create file'
        );

        $this->assertEquals(
            '{"token":"test-token"}',
            file_get_contents($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::TOKEN_FILE),
            'Should have right content'
        );

        // Test with not writable file
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::TOKEN_FILE, 0444)
            ->at($this->root);

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::TOKEN_FILE),
            'Should create file'
        );

        $result = $storage->storeToken('test-token');
        $this->assertEquals(
            false,
            $result,
            'Should return false on error'
        );
    }

    public function testStoreScenarios()
    {
        $storage = new FileStorage($this->root->url());

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::SCENARIOS_FILE),
            'File should not exist'
        );

        $storage->storeScenarios(['test-scenarios']);

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::SCENARIOS_FILE),
            'Should create file'
        );

        $this->assertEquals(
            '{"scenarios":["test-scenarios"]}',
            file_get_contents($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::SCENARIOS_FILE),
            'Should have right content'
        );

        // Test with not writable file
        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::SCENARIOS_FILE, 0444)
            ->at($this->root);

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::SCENARIOS_FILE),
            'Should create file'
        );

        $result = $storage->storeScenarios(['test-scenario']);
        $this->assertEquals(
            false,
            $result,
            'Should return false on error'
        );
    }

    public function testPrivateOrProtectedMethods()
    {
        $storage = new FileStorage($this->root->url());

        vfsStream::newFile(Constants::ENV_DEV . '-' . FileStorage::TOKEN_FILE, 0777)
            ->at($this->root)
            ->setContent('["json" => "bad-json"}');

        $result = PHPUnitUtil::callMethod(
            $storage,
            'readFile',
            [$this->root->url() . '/' . Constants::ENV_DEV . '-' . FileStorage::TOKEN_FILE]
        );

        $this->assertEquals(
            [],
            $result,
            'Should return empty array if bad json'
        );
    }
}
