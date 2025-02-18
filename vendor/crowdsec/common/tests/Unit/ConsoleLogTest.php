<?php

declare(strict_types=1);

namespace CrowdSec\Common\Tests\Unit;

/**
 * Test for console logger.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\Common\Logger\ConsoleLog;
use CrowdSec\Common\Tests\PHPUnitUtil;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CrowdSec\Common\Logger\ConsoleLog::__construct
 * @covers \CrowdSec\Common\Logger\AbstractLog::getMonologLogger
 *
 * @uses   \CrowdSec\Common\Logger\AbstractLog::__construct
 */
final class ConsoleLogTest extends TestCase
{
    public function testConstruct()
    {
        $logger = new ConsoleLog();

        $this->assertEquals(
            'common-console-logger',
            $logger->getMonologLogger()->getName(),
            'Log name should be default one if not specified'
        );

        $logger = new ConsoleLog([], 'test-name');

        $this->assertEquals(
            'test-name',
            $logger->getMonologLogger()->getName(),
            'Log name should be configurable'
        );

        $handlers = $logger->getMonologLogger()->getHandlers();
        $this->assertCount(1, $handlers, 'Should have one handler');
        $handler = $handlers[0];
        $this->assertEquals('Monolog\Handler\StreamHandler', \get_class($handler), 'Handler should be Stream');
        $this->assertEquals('php://stdout', $handler->getUrl(), 'Handler url should be php://stdout');
        $expectedLogLevel = class_exists('Monolog\Level') ? \Monolog\Level::from(100) : 100;

        $this->assertEquals($expectedLogLevel, $handler->getLevel(), 'Handler should have default debug log level');

        $logger = new ConsoleLog(['level' => 550]);
        $handlers = $logger->getMonologLogger()->getHandlers();
        $handler = $handlers[0];
        $expectedLogLevel = class_exists('Monolog\Level') ? \Monolog\Level::from(550) : 550;
        $this->assertEquals($expectedLogLevel, $handler->getLevel(), 'Handler should have configured log level');

        $error = '';
        // Monolog v3
        if (\class_exists('Monolog\Level')) {
            try {
                $logger = new ConsoleLog(['level' => 888]);
            } catch (\Psr\Log\InvalidArgumentException $e) {
                $error = $e->getMessage();
            }

            PHPUnitUtil::assertRegExp(
                $this,
                '/Level "888" is not defined, use one of/',
                $error,
                'Should throw an error'
            );
        } else {
            try {
                $logger = new ConsoleLog(['level' => 'do-not-exist']);
            } catch (\Psr\Log\InvalidArgumentException $e) {
                $error = $e->getMessage();
            }

            PHPUnitUtil::assertRegExp(
                $this,
                '/Level "do-not-exist" is not defined, use one of: 100, 200, 250, 300, 400, 500, 550, 600/',
                $error,
                'Should throw an error'
            );
        }
    }
}
