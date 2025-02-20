<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient\Configuration\Metrics;

use CrowdSec\Common\Configuration\AbstractConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * The LAPI client metrics items configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Items extends AbstractConfiguration
{
    /** @var array<string> The list of each configuration tree key */
    protected $keys = [
        'name',
        'value',
        'unit',
        'labels',
    ];

    /**
     * Keep only necessary configs
     * Override because $configs is an array of array (metrics item) and we want to clean each item.
     */
    public function cleanConfigs(array $configs): array
    {
        $result = [];
        foreach ($configs as $config) {
            $result[] = array_intersect_key($config, array_flip($this->keys));
        }

        return $result;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('metricsItemsConfig');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->arrayPrototype()
                ->children()
                    ->scalarNode('name')
                        ->isRequired()->cannotBeEmpty()
                    ->end()
                    ->integerNode('value')->isRequired()
                        ->min(0)
                    ->end()
                    ->scalarNode('unit')->isRequired()->cannotBeEmpty()->end()
                    ->variableNode('labels')
                        // Remove empty labels totally
                        ->beforeNormalization()
                            ->ifTrue(function ($value) {
                                return empty($value);
                            })
                            ->thenUnset()
                        ->end()
                        ->validate()
                            ->ifTrue(function ($value) {
                                // Ensure all values in the array are strings
                                if (!is_array($value)) {
                                    return true;
                                }
                                foreach ($value as $val) {
                                    if (!is_string($val)) {
                                        return true;
                                    }
                                }

                                return false;
                            })
                            ->thenInvalid('Labels must be an array of key-value pairs with string values.')
                        ->end()
                        ->info('Optional labels as key-value pairs.')
                    ->end()
                ->end()
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
