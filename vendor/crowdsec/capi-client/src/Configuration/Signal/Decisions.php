<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Configuration\Signal;

use CrowdSec\CapiClient\Constants;
use CrowdSec\Common\Configuration\AbstractConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * The Signal decisions configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Decisions extends AbstractConfiguration
{
    /**
     * @var string[]
     */
    protected $keys = [
        'duration',
        'scenario',
        'origin',
        'scope',
        'simulated',
        'id',
        'type',
        'value',
        'uuid',
    ];

    /**
     * Keep only necessary configs
     * Override because $configs is an array of array (decision) and we want to clean each decision.
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
        $treeBuilder = new TreeBuilder('signalDecisionsConfig');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->arrayPrototype()
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('duration')
                        ->isRequired()->cannotBeEmpty()
                    ->end()
                    ->scalarNode('scenario')
                        ->isRequired()->cannotBeEmpty()
                        ->validate()
                        ->ifTrue(function (string $value) {
                            return 1 !== preg_match(Constants::SCENARIO_REGEX, $value);
                        })
                        ->thenInvalid('Invalid scenario. Must match with ' . Constants::SCENARIO_REGEX . ' regex')
                        ->end()
                    ->end()
                    ->scalarNode('origin')
                        ->isRequired()->cannotBeEmpty()
                    ->end()
                    ->scalarNode('scope')
                        ->isRequired()->cannotBeEmpty()
                    ->end()
                    ->booleanNode('simulated')
                        ->defaultFalse()
                    ->end()
                    ->integerNode('id')->isRequired()
                        ->min(0)
                    ->end()
                    ->scalarNode('uuid')->cannotBeEmpty()->end()
                    ->scalarNode('type')
                        ->isRequired()->cannotBeEmpty()
                    ->end()
                    ->scalarNode('value')
                        ->isRequired()->cannotBeEmpty()
                    ->end()
                ->end()
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
