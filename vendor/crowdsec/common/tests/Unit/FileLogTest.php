<?php

declare(strict_types=1);

namespace CrowdSec\Common\Tests\Unit;

/**
 * Test for file logger.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\Common\Logger\FileLog;
use CrowdSec\Common\Tests\PHPUnitUtil;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CrowdSec\Common\Logger\FileLog::__construct
 * @covers \CrowdSec\Common\Logger\AbstractLog::__construct
 * @covers \CrowdSec\Common\Logger\FileLog::buildFileHandler
 * @covers \CrowdSec\Common\Logger\AbstractLog::getMonologLogger
 * @covers \CrowdSec\Common\Logger\AbstractLog::info
 * @covers \CrowdSec\Common\Logger\AbstractLog::error
 * @covers \CrowdSec\Common\Logger\AbstractLog::emergency
 * @covers \CrowdSec\Common\Logger\AbstractLog::alert
 * @covers \CrowdSec\Common\Logger\AbstractLog::critical
 * @covers \CrowdSec\Common\Logger\AbstractLog::warning
 * @covers \CrowdSec\Common\Logger\AbstractLog::notice
 * @covers \CrowdSec\Common\Logger\AbstractLog::debug
 * @covers \CrowdSec\Common\Logger\AbstractLog::log
 */
final class FileLogTest extends TestCase
{
    public const TMP_DIR = '/tmp';
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * @var string
     */
    private $debugRotateFile;
    /**
     * @var string
     */
    private $prodRotateFile;
    /**
     * @var string
     */
    private $debugFile;
    /**
     * @var string
     */
    private $prodFile;

    /**
     * set up test environment.
     */
    public function setUp(): void
    {
        $this->root = vfsStream::setup(self::TMP_DIR);
        $currentDate = date('Y-m-d');
        $this->debugFile = 'debug.log';
        $this->prodFile = 'prod.log';
        $this->debugRotateFile = 'debug-' . $currentDate . '.log';
        $this->prodRotateFile = 'prod-' . $currentDate . '.log';
    }

    public function testProdLog()
    {
        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . $this->debugFile),
            'Debug File should not exist'
        );

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . $this->prodFile),
            'Prod File should not exist'
        );

        $logger = new FileLog(['log_directory_path' => $this->root->url()]);

        $handlers = $logger->getMonologLogger()->getHandlers();
        $this->assertCount(1, $handlers, 'Should have one handler');

        // Test prod log
        $logger->info('', [
            'type' => 'TEST1',
        ]);

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . $this->prodFile),
            'Prod File should exist'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*200.*"type":"TEST1"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Log content should be correct'
        );

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . $this->debugFile),
            'Debug File should not exist'
        );
    }

    public function testProdLogRotate()
    {
        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . $this->debugRotateFile),
            'Debug File should not exist'
        );

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . $this->prodRotateFile),
            'Prod File should not exist'
        );

        $logger = new FileLog(['log_directory_path' => $this->root->url(), 'log_rotator' => true]);

        $handlers = $logger->getMonologLogger()->getHandlers();
        $this->assertCount(1, $handlers, 'Should have one handler');

        // Test prod log
        $logger->info('', [
            'type' => 'TEST1',
        ]);

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . $this->prodRotateFile),
            'Prod File should exist'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*200.*"type":"TEST1"/',
            file_get_contents($this->root->url() . '/' . $this->prodRotateFile),
            'Log content should be correct'
        );

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . $this->debugRotateFile),
            'Debug File should not exist'
        );
    }

    public function testDebugLog()
    {
        $logger = new FileLog([
            'log_directory_path' => $this->root->url(),
            'debug_mode' => true
        ]);

        $handlers = $logger->getMonologLogger()->getHandlers();
        $this->assertCount(2, $handlers, 'Should have 2 handlers');

        $logger->info('', [
            'type' => 'TEST2',
        ]);

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . $this->prodFile),
            'Prod File should exist'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*200.*"type":"TEST2"/',
            file_get_contents($this->root->url() . '/' . $this->prodFile),
            'Prod log content should be correct'
        );

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . $this->debugFile),
            'Debug File should  exist'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*200.*"type":"TEST2"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Debug log content should be correct'
        );

        $logger->emergency('', [
            'type' => 'EMERGENCY',
        ]);
        $logger->alert('', [
            'type' => 'ALERT',
        ]);
        $logger->critical('', [
            'type' => 'CRITICAL',
        ]);
        $logger->error('', [
            'type' => 'ERROR',
        ]);
        $logger->warning('', [
            'type' => 'WARNING',
        ]);
        $logger->notice('', [
            'type' => 'NOTICE',
        ]);
        $logger->info('', [
            'type' => 'INFO',
        ]);
        $logger->debug('', [
            'type' => 'DEBUG',
        ]);

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*100.*"type":"DEBUG"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Debug log content should be correct'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*200.*"type":"INFO"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Debug log content should be correct'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*250.*"type":"NOTICE"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Debug log content should be correct'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*300.*"type":"WARNING"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Debug log content should be correct'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*400.*"type":"ERROR"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Debug log content should be correct'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*500.*"type":"CRITICAL"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Debug log content should be correct'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*550.*"type":"ALERT"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Debug log content should be correct'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*600.*"type":"EMERGENCY"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Debug log content should be correct'
        );

        $logger->log(100, '', [
            'type' => 'LOG WITH LEVEL',
        ]);

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*100.*"type":"LOG WITH LEVEL"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Debug log content should be correct'
        );
    }

    public function testDebugLogRotate()
    {
        $logger = new FileLog([
            'log_directory_path' => $this->root->url(),
            'debug_mode' => true,
            'log_rotator' => true,
        ]);

        $handlers = $logger->getMonologLogger()->getHandlers();
        $this->assertCount(2, $handlers, 'Should have 2 handlers');

        $logger->info('', [
            'type' => 'TEST2',
        ]);

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . $this->prodRotateFile),
            'Prod File should exist'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*200.*"type":"TEST2"/',
            file_get_contents($this->root->url() . '/' . $this->prodRotateFile),
            'Prod log content should be correct'
        );

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . $this->debugRotateFile),
            'Debug File should  exist'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*200.*"type":"TEST2"/',
            file_get_contents($this->root->url() . '/' . $this->debugRotateFile),
            'Debug log content should be correct'
        );
    }

    public function testDisableProdLog()
    {
        $logger = new FileLog([
            'log_directory_path' => $this->root->url(),
            'debug_mode' => true,
            'disable_prod_log' => true,
            'log_rotator' => true,
        ]);
        $logger->info('', [
            'type' => 'TEST3',
        ]);

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . $this->prodRotateFile),
            'Prod File should not exist'
        );

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . $this->debugRotateFile),
            'Debug File should  exist'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*200.*"type":"TEST3"/',
            file_get_contents($this->root->url() . '/' . $this->debugRotateFile),
            'Debug log content should be correct'
        );
    }

    public function testFormat()
    {
        $logger = new FileLog([
            'log_directory_path' => $this->root->url(),
            'log_rotator' => true,
        ]);
        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . $this->prodRotateFile),
            'Prod File should not exist'
        );
        $logger->error('error-message', [
            'type' => 'TEST3',
        ]);

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . $this->prodRotateFile),
            'Prod File should  exist'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*|400|error-message|.*"type":"TEST3"/',
            file_get_contents($this->root->url() . '/' . $this->prodRotateFile),
            'Prod log content should formatted with default format'
        );

        file_put_contents($this->root->url() . '/' . $this->prodRotateFile, '');
        $this->assertEmpty(file_get_contents($this->root->url() . '/' . $this->prodRotateFile),
            'Prod log content should be empty');

        $logger = new FileLog([
            'log_directory_path' => $this->root->url(),
            'format' => '%message%|%level%|%message%|%level%',
            'log_rotator' => true,
        ]);
        $logger->error('error-message', [
            'type' => 'TEST3',
        ]);
        $this->assertEquals('error-message|400|error-message|400', file_get_contents($this->root->url() . '/' .
                                                                                      $this->prodRotateFile),
            'Prod log content should be well formatted');

        file_put_contents($this->root->url() . '/' . $this->prodRotateFile, '');
        $this->assertEmpty(file_get_contents($this->root->url() . '/' . $this->prodRotateFile),
            'Prod log content should be empty');

        $error = '';
        try {
            new FileLog([
                'log_directory_path' => $this->root->url(),
                'format' => ['bad-config' => true],
                'log_rotator' => true,
            ]);
        } catch (\TypeError $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/must be of.*type.*string.*array given/',
            $error,
            'Should throw an error'
        );
    }
}
