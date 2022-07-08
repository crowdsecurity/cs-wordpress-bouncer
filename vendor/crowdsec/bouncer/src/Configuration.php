<?php

namespace CrowdSecBouncer;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The Library configuration. You'll find here all configuration possible. Used when instantiating the library.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('config');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                // LAPI Connection
                ->scalarNode('api_key')->isRequired()->end()
                ->scalarNode('api_url')->defaultValue(Constants::DEFAULT_LAPI_URL)->end()
                ->scalarNode('api_user_agent')->defaultValue(Constants::BASE_USER_AGENT)->end()
                ->integerNode('api_timeout')->min(Constants::API_TIMEOUT)->defaultValue(Constants::API_TIMEOUT)->end()
                // Debug
                ->scalarNode('forced_test_ip')->defaultValue('')->end()
                ->scalarNode('forced_test_forwarded_ip')->defaultValue('')->end()
                ->booleanNode('debug_mode')->defaultValue(false)->end()
                ->scalarNode('log_directory_path')->end()
                ->booleanNode('display_errors')->defaultValue(false)->end()
                // Bouncer
                ->enumNode('bouncing_level')
                    ->values(
                        [
                            Constants::BOUNCING_LEVEL_DISABLED,
                            Constants::BOUNCING_LEVEL_NORMAL,
                            Constants::BOUNCING_LEVEL_FLEX
                        ]
                    )
                    ->defaultValue(Constants::BOUNCING_LEVEL_NORMAL)
                ->end()
                ->enumNode('max_remediation_level')
                    ->values(Constants::ORDERED_REMEDIATIONS)
                    ->defaultValue(Constants::REMEDIATION_BAN)
                ->end()
                ->enumNode('fallback_remediation')
                    ->values(Constants::ORDERED_REMEDIATIONS)
                    ->defaultValue(Constants::REMEDIATION_CAPTCHA)
                ->end()
                ->arrayNode('trust_ip_forward_array')
                    ->arrayPrototype()
                        ->scalarPrototype()->end()
                    ->end()
                ->end()
                ->arrayNode('excluded_uris')
                    ->scalarPrototype()->end()
                ->end()
                // Cache
                ->booleanNode('stream_mode')->defaultValue(false)->end()
                ->enumNode('cache_system')
                    ->values(
                        [
                            Constants::CACHE_SYSTEM_PHPFS,
                            Constants::CACHE_SYSTEM_REDIS,
                            Constants::CACHE_SYSTEM_MEMCACHED
                        ]
                    )
                    ->defaultValue(Constants::CACHE_SYSTEM_PHPFS)
                ->end()
                ->scalarNode('fs_cache_path')->end()
                ->scalarNode('redis_dsn')->end()
                ->scalarNode('memcached_dsn')->end()
                ->integerNode('clean_ip_cache_duration')
                    ->min(1)->defaultValue(Constants::CACHE_EXPIRATION_FOR_CLEAN_IP)
                ->end()
                ->integerNode('bad_ip_cache_duration')
                    ->min(1)->defaultValue(Constants::CACHE_EXPIRATION_FOR_BAD_IP)
                ->end()
                ->integerNode('captcha_cache_duration')
                    ->min(1)->defaultValue(Constants::CACHE_EXPIRATION_FOR_CAPTCHA)
                ->end()
                ->integerNode('geolocation_cache_duration')
                    ->min(1)->defaultValue(Constants::CACHE_EXPIRATION_FOR_GEO)
                ->end()
                // Geolocation
                ->arrayNode('geolocation')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('save_result')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->enumNode('type')
                            ->defaultValue(Constants::GEOLOCATION_TYPE_MAXMIND)
                            ->values([Constants::GEOLOCATION_TYPE_MAXMIND])
                        ->end()
                        ->arrayNode(Constants::GEOLOCATION_TYPE_MAXMIND)
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('database_type')
                                    ->defaultValue(Constants::MAXMIND_COUNTRY)
                                    ->values([Constants::MAXMIND_COUNTRY, Constants::MAXMIND_CITY])
                                ->end()
                                ->scalarNode('database_path')
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
