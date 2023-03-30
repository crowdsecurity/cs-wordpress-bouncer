<?php

declare(strict_types=1);
/**
 * Some helpers for Unit test.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

namespace CrowdSec\RemediationEngine\Tests;

use PHPUnit\Runner\Version;

class PHPUnitUtil
{
    public static function assertRegExp($testCase, $pattern, $string, $message = '')
    {
        if (version_compare(self::getPHPUnitVersion(), '9.0', '>=')) {
            $testCase->assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            $testCase->assertRegExp($pattern, $string, $message);
        }
    }

    public static function assertDoesNotMatchRegExp($testCase, $pattern, $string, $message = '')
    {
        if (version_compare(self::getPHPUnitVersion(), '9.0', '>=')) {
            $testCase->assertDoesNotMatchRegularExpression($pattern, $string, $message);
        } else {
            $testCase->assertNotRegExp($pattern, $string, $message);
        }
    }

    public static function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    public static function getPHPUnitVersion(): string
    {
        return Version::id();
    }
}
