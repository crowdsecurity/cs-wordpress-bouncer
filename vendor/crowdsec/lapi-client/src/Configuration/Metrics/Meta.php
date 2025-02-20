<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient\Configuration\Metrics;

use CrowdSec\Common\Configuration\AbstractConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * The LAPI client metrics meta configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Meta extends AbstractConfiguration
{
    /** @var array<string> The list of each configuration tree key */
    protected $keys = [
        'window_size_seconds',
        'utc_now_timestamp',
    ];

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('metricsMetaConfig');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->integerNode('window_size_seconds')->isRequired()->min(0)->end()
            ->integerNode('utc_now_timestamp')->isRequired()->min(0)->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
