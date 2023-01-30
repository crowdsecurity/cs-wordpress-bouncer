<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\RequestHandler;

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
abstract class AbstractRequestHandler
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
     *
     * @return mixed
     */
    public function getConfig(string $name)
    {
        return (isset($this->configs[$name])) ? $this->configs[$name] : null;
    }
}
