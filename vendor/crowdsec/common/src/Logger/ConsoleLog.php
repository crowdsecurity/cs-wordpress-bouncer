<?php

declare(strict_types=1);

namespace CrowdSec\Common\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * A Monolog logger implementation to print log in terminal.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class ConsoleLog extends AbstractLog
{
    /**
     * @var string The logger name
     */
    public const LOGGER_NAME = 'common-console-logger';

    public function __construct(array $configs = [], string $name = self::LOGGER_NAME)
    {
        parent::__construct($configs, $name);
        $level = $configs['level'] ?? Logger::DEBUG;
        $handler = new StreamHandler('php://stdout', $level);
        $handler->setFormatter(new LineFormatter($this->format));
        $this->pushHandler($handler);
    }
}
