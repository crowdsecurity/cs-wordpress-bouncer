<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient\Configuration;

use CrowdSec\Common\Configuration\AbstractConfiguration;
use CrowdSec\LapiClient\Constants;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * The LAPI client metrics properties configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Metrics extends AbstractConfiguration
{
    /** @var array<string> The list of each configuration tree key */
    protected $keys = [
        'name',
        'type',
        'last_pull',
        'version',
        'os',
        'feature_flags',
        'utc_startup_timestamp',
    ];

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('metricsConfig');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('type')->isRequired()->defaultValue(Constants::METRICS_TYPE)->end()
            ->integerNode('last_pull')->end()
            ->scalarNode('version')->isRequired()->cannotBeEmpty()->end()
            ->integerNode('utc_startup_timestamp')->isRequired()->min(0)->end()
            ->arrayNode('os')
                ->children()
                    ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('version')->isRequired()->cannotBeEmpty()->end()
                ->end()
            ->end()
            ->arrayNode('feature_flags')
                ->scalarPrototype()->end()
                ->defaultValue([])
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
