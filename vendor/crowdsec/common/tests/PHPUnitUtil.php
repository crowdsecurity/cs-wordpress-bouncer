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

namespace CrowdSec\Common\Tests;

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

    /**
     * Sets a protected property on a given object via reflection.
     *
     * @see https://stackoverflow.com/a/37667018/7497291
     *
     * @param $object   - instance in which protected value is being modified
     * @param $property - property on instance being modified
     * @param $value    - new value of the property being modified
     *
     * @return void
     */
    public static function setProtectedProperty($object, $property, $value)
    {
        $reflection = new \ReflectionClass($object);
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($object, $value);
    }
}
