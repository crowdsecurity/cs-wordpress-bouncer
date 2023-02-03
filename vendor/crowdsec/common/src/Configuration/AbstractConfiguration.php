<?php

declare(strict_types=1);

namespace CrowdSec\Common\Configuration;

use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The abstract configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractConfiguration implements ConfigurationInterface
{
    /**
     * @var string[]
     */
    protected $keys = [];

    /**
     * Keep only necessary configs.
     */
    public function cleanConfigs(array $configs): array
    {
        return array_intersect_key($configs, array_flip($this->keys));
    }
}
