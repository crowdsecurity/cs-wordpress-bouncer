<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Configuration;

use CrowdSec\CapiClient\Constants;
use CrowdSec\Common\Configuration\AbstractConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * The Watcher configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Watcher extends AbstractConfiguration
{
    /**
     * @var string[]
     */
    protected $keys = [
        'env',
        'machine_id_prefix',
        'user_agent_suffix',
        'user_agent_version',
        'scenarios',
        'api_timeout',
        'api_connect_timeout',
        'metrics',
    ];

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('watcherConfig');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->enumNode('env')
                ->values(
                    [
                        Constants::ENV_DEV,
                        Constants::ENV_PROD,
                    ]
                )
                ->defaultValue(Constants::ENV_DEV)
            ->end()
            ->scalarNode('machine_id_prefix')
                ->validate()
                ->ifTrue(function (string $value) {
                    return 1 !== preg_match('#^[a-z0-9]{0,48}$#', $value);
                })
                ->thenInvalid('Invalid machine id prefix. Length must be <= 48. Allowed chars are a-z0-9')
                ->end()
            ->end()
            ->scalarNode('user_agent_suffix')
                ->validate()
                ->ifTrue(function (string $value) {
                    return 1 !== preg_match('#^[A-Za-z0-9]{0,16}$#', $value);
                })
                ->thenInvalid('Invalid user agent suffix. Length must be <= 16. Allowed chars are A-Za-z0-9')
                ->end()
            ->end()
            ->scalarNode('user_agent_version')
                ->validate()
                ->ifTrue(function (string $value) {
                    if (!empty($value)) {
                        return 1 !== preg_match(Constants::VERSION_REGEX, $value);
                    }

                    return true;
                })
                ->thenInvalid('Invalid user agent version. Must match vX.Y.Z format')
                ->end()
                ->defaultValue(Constants::VERSION)
            ->end()
            ->arrayNode('scenarios')->isRequired()->cannotBeEmpty()
                ->validate()
                    ->ifTrue(function (array $scenarios) {
                        foreach ($scenarios as $scenario) {
                            if (1 !== preg_match(Constants::SCENARIO_REGEX, $scenario)) {
                                return true;
                            }
                        }

                        return false;
                    })
                    ->thenInvalid('Each scenario must match ' . Constants::SCENARIO_REGEX . ' regex')
                ->end()
                ->validate()
                    ->ifArray()
                    ->then(function (array $value) {
                        return array_values(array_unique($value));
                    })
                ->end()
                ->scalarPrototype()->cannotBeEmpty()->end()
            ->end()
            ->integerNode('api_timeout')->defaultValue(Constants::API_TIMEOUT)->end()
            ->integerNode('api_connect_timeout')->defaultValue(Constants::API_CONNECT_TIMEOUT)->end()
        ->end()
        ;
        $this->addMetricsNodes($rootNode);

        return $treeBuilder;
    }

    /**
     * Metrics settings.
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     *
     * @return void
     *
     * @throws \InvalidArgumentException|\RuntimeException
     */
    private function addMetricsNodes($rootNode)
    {
        $rootNode->children()
            ->arrayNode('metrics')
                ->children()
                    ->arrayNode('bouncer')
                        ->children()
                            ->scalarNode('last_pull')
                                ->cannotBeEmpty()
                                ->validate()
                                ->ifTrue(function (string $value) {
                                    return 1 !== preg_match(Constants::ISO8601_REGEX, $value);
                                })
                                ->thenInvalid(
                                    'Invalid metrics_bouncer_last_pull. Must match with ' . Constants::ISO8601_REGEX
                                )
                                ->end()
                            ->end()
                            ->scalarNode('custom_name')->cannotBeEmpty()
                                ->validate()
                                ->ifTrue(function (string $value) {
                                    return 1 !== preg_match('#^[A-Za-z0-9]{1,32}$#', $value);
                                })
                                ->thenInvalid(
                                    'Invalid bouncer custom name. Length must be <= 32. Allowed chars are A-Za-z0-9'
                                )
                                ->end()
                            ->end()
                            ->scalarNode('version')->cannotBeEmpty()
                                ->validate()
                                ->ifTrue(function (string $value) {
                                    return 1 !== preg_match(Constants::VERSION_REGEX, $value);
                                })
                                ->thenInvalid('Invalid bouncer version. Must match vX.Y.Z format')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('machine')
                        ->children()
                            ->scalarNode('last_update')
                                ->cannotBeEmpty()
                                ->validate()
                                ->ifTrue(function (string $value) {
                                    return 1 !== preg_match(Constants::ISO8601_REGEX, $value);
                                })
                                ->thenInvalid(
                                    'Invalid metrics_machine_last_update. Must match with ' . Constants::ISO8601_REGEX
                                )
                                ->end()
                            ->end()
                            ->scalarNode('name')->cannotBeEmpty()
                                ->validate()
                                ->ifTrue(function (string $value) {
                                    return 1 !== preg_match('#^[A-Za-z0-9]{1,32}$#', $value);
                                })
                                ->thenInvalid('Invalid machine name. Length must be <= 32. Allowed chars are A-Za-z0-9')
                                ->end()
                            ->end()
                            ->scalarNode('last_push')
                                ->cannotBeEmpty()
                                ->validate()
                                ->ifTrue(function (string $value) {
                                    return 1 !== preg_match(Constants::ISO8601_REGEX, $value);
                                })
                                ->thenInvalid(
                                    'Invalid metrics_machine_last_push. Must match with ' . Constants::ISO8601_REGEX
                                )
                                ->end()
                            ->end()
                            ->scalarNode('version')->cannotBeEmpty()
                                ->validate()
                                ->ifTrue(function (string $value) {
                                    return 1 !== preg_match(Constants::VERSION_REGEX, $value);
                                })
                                ->thenInvalid('Invalid machine version. Must match vX.Y.Z format')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
