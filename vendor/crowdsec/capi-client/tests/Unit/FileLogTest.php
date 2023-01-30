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

use CrowdSec\CapiClient\Logger\FileLog;
use CrowdSec\CapiClient\Tests\PHPUnitUtil;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CrowdSec\CapiClient\Logger\FileLog::__construct
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
        $this->debugFile = 'debug-' . $currentDate . '.log';
        $this->prodFile = 'prod-' . $currentDate . '.log';
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

    public function testDebugLog()
    {
        $logger = new FileLog([
            'log_directory_path' => $this->root->url(),
            'debug_mode' => true,
        ]);

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
    }

    public function testDisableProdLog()
    {
        $logger = new FileLog([
            'log_directory_path' => $this->root->url(),
            'debug_mode' => true,
            'disable_prod_log' => true,
        ]);
        $logger->info('', [
            'type' => 'TEST3',
        ]);

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . $this->prodFile),
            'Prod File should not exist'
        );

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . $this->debugFile),
            'Debug File should  exist'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*200.*"type":"TEST3"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Debug log content should be correct'
        );
    }
}
