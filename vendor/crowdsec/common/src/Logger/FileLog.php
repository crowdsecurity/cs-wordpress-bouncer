<?php

declare(strict_types=1);

namespace CrowdSec\Common\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

/**
 * A Monolog logger implementation with 2 files : debug and prod.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class FileLog extends AbstractLog
{
    /**
     * @var string The debug log filename
     */
    public const DEBUG_FILE = 'debug.log';
    /**
     * @var string The logger name
     */
    public const LOGGER_NAME = 'common-file-logger';
    /**
     * @var string The prod log filename
     */
    public const PROD_FILE = 'prod.log';

    public function __construct(array $configs = [], string $name = self::LOGGER_NAME)
    {
        parent::__construct($configs, $name);
        $logDir = $configs['log_directory_path'] ?? __DIR__ . '/.logs';
        if (empty($configs['disable_prod_log'])) {
            $logPath = $logDir . '/' . self::PROD_FILE;
            $fileHandler = new RotatingFileHandler($logPath, 0, Logger::INFO);
            $fileHandler->setFormatter(new LineFormatter($this->format));
            $this->pushHandler($fileHandler);
        }

        if (!empty($configs['debug_mode'])) {
            $debugLogPath = $logDir . '/' . self::DEBUG_FILE;
            $debugFileHandler = new RotatingFileHandler($debugLogPath, 0, Logger::DEBUG);
            $debugFileHandler->setFormatter(new LineFormatter($this->format));
            $this->pushHandler($debugFileHandler);
        }
    }
}
