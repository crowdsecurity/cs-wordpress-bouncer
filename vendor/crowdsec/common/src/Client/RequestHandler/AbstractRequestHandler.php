<?php

declare(strict_types=1);

namespace CrowdSec\Common\Client\RequestHandler;

/**
 * Request handler abstraction.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractRequestHandler implements RequestHandlerInterface
{
    /**
     * @var array
     */
    private $configs;

    public function __construct(array $configs = [])
    {
        $this->configs = $configs;
    }

    /**
     * Retrieve a config value by name.
     */
    public function getConfig(string $name)
    {
        return (isset($this->configs[$name])) ? $this->configs[$name] : null;
    }
}
