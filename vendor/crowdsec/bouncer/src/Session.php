<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

/**
 * The Library session helper.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Session
{
    /**
     * Return a session variable, null if not set.
     */
    public static function getSessionVariable(string $name)
    {
        if (!isset($_SESSION[$name])) {
            return null;
        }

        return $_SESSION[$name];
    }

    /**
     * Set a session variable.
     */
    public static function setSessionVariable(string $name, $value): void
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Unset a session variable, throw an error if this does not exist.
     *
     * @return void;
     */
    public static function unsetSessionVariable(string $name): void
    {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
    }
}
