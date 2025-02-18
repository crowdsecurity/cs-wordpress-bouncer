<?php

declare(strict_types=1);

namespace CrowdSec\Common\Logger;

use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Abstract class for Monolog logger implementation.
 *
 * Since Monolog v3, Logger is a final class, so we can't extend it.
 * Furthermore, level constants have been deprecated; that's why we define the values directly.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 *
 */
abstract class AbstractLog implements LoggerInterface
{
    /**
     * Detailed debug information.
     */
    public const DEBUG = 100;

    /**
     * Interesting events.
     */
    public const INFO = 200;

    /**
     * Uncommon events.
     */
    public const NOTICE = 250;

    /**
     * Exceptional occurrences that are not errors.
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    public const WARNING = 300;

    /**
     * Runtime errors.
     */
    public const ERROR = 400;

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     */
    public const CRITICAL = 500;

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    public const ALERT = 550;

    /**
     * Urgent alert.
     */
    public const EMERGENCY = 600;

    /**
     * @var string Format of log messages
     */
    protected $format = "%datetime%|%level%|%message%|%context%\n";
    /**
     * @var Logger
     */
    private $monologLogger;

    public function __construct(array $configs, string $name)
    {
        $this->format = $configs['format'] ?? $this->format;
        $this->monologLogger = new Logger($name);
    }

    /**
     * System is unusable.
     */
    public function emergency($message, array $context = []): void
    {
        $this->monologLogger->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     */
    public function alert($message, array $context = []): void
    {
        $this->monologLogger->alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     */
    public function critical($message, array $context = []): void
    {
        $this->monologLogger->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    public function error($message, array $context = []): void
    {
        $this->monologLogger->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     */
    public function warning($message, array $context = []): void
    {
        $this->monologLogger->warning($message, $context);
    }

    /**
     * Normal but significant events.
     */
    public function notice($message, array $context = []): void
    {
        $this->monologLogger->notice($message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     */
    public function info($message, array $context = []): void
    {
        $this->monologLogger->info($message, $context);
    }

    /**
     * Detailed debug information.
     */
    public function debug($message, array $context = []): void
    {
        $this->monologLogger->debug($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @throws \InvalidArgumentException
     */
    public function log($level, $message, array $context = []): void
    {
        $this->monologLogger->log($level, $message, $context);
    }

    public function getMonologLogger(): Logger
    {
        return $this->monologLogger;
    }
}
