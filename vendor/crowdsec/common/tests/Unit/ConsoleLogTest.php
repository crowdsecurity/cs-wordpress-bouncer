<?php

declare(strict_types=1);

namespace CrowdSec\Common\Tests\Unit;

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

use CrowdSec\Common\Logger\ConsoleLog;
use CrowdSec\Common\Tests\PHPUnitUtil;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CrowdSec\Common\Logger\ConsoleLog::__construct
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
            $logger->getName(),
            'Log name should be default one if not specified'
        );

        $logger = new ConsoleLog([], 'test-name');

        $this->assertEquals(
            'test-name',
            $logger->getName(),
            'Log name should be configurable'
        );

        $handlers = $logger->getHandlers();
        $this->assertCount(1, $handlers, 'Should have one handler');
        $handler = $handlers[0];
        $this->assertEquals('Monolog\Handler\StreamHandler', \get_class($handler), 'Handler should be Stream');
        $this->assertEquals('php://stdout', $handler->getUrl(), 'Handler url should be php://stdout');
        $this->assertEquals(ConsoleLog::DEBUG, $handler->getLevel(), 'Handler should have default debug log level');

        $logger = new ConsoleLog(['level' => ConsoleLog::ALERT]);
        $handlers = $logger->getHandlers();
        $handler = $handlers[0];
        $this->assertEquals(ConsoleLog::ALERT, $handler->getLevel(), 'Handler should have configured log level');

        $error = '';
        try {
            $logger = new ConsoleLog(['level' => 'do-no-exist']);
        } catch (\Psr\Log\InvalidArgumentException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Level "do-no-exist" is not defined, use one of/',
            $error,
            'Should throw an error'
        );
    }
}
