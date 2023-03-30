<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Configuration;

use CrowdSec\Common\Configuration\AbstractConfiguration;

/**
 * The remediation cache common configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractCache extends AbstractConfiguration
{
    /**
     * @var string[]
     */
    protected $keys = [
        'use_cache_tags',
    ];

    /**
     * Common cache settings.
     *
     * @return void
     */
    protected function addCommonNodes($rootNode)
    {
        $rootNode->children()
            ->booleanNode('use_cache_tags')->defaultFalse()->end()
        ->end();
    }
}
