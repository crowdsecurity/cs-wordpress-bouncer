<?php

namespace CrowdSecBouncer;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
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
     * @throws InvalidArgumentException|RuntimeException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('config');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $this->validate($rootNode);
        $this->addConnectionNodes($rootNode);
        $this->addDebugNodes($rootNode);
        $this->addBouncerNodes($rootNode);
        $this->addCacheNodes($rootNode);
        $this->addGeolocationNodes($rootNode);

        return $treeBuilder;
    }

    /**
     * Bouncer settings
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     * @return void
     * @throws InvalidArgumentException
     */
    private function addBouncerNodes($rootNode)
    {
        $rootNode->children()
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
        ->end();
    }

    /**
     * Cache settings
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     * @return void
     * @throws InvalidArgumentException
     */
    private function addCacheNodes($rootNode)
    {
        $rootNode->children()
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
        ->end();
    }

    /**
     * LAPI connection settings
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     * @return void
     * @throws InvalidArgumentException
     */
    private function addConnectionNodes($rootNode)
    {
        $rootNode->children()
            ->enumNode('auth_type')
                ->values(
                    [
                        Constants::AUTH_KEY,
                        Constants::AUTH_TLS,
                    ]
                )
                ->defaultValue(Constants::AUTH_KEY)
            ->end()
            ->scalarNode('api_key')->end()
            ->scalarNode('api_url')->defaultValue(Constants::DEFAULT_LAPI_URL)->end()
            ->scalarNode('api_user_agent')->defaultValue(Constants::BASE_USER_AGENT)->end()
            ->scalarNode('tls_cert_path')
                ->info('Absolute path to the Bouncer certificate')->defaultValue('')
            ->end()
                ->scalarNode('tls_key_path')
            ->info('Absolute path to the Bouncer key')->defaultValue('')
            ->end()
            ->scalarNode('tls_ca_cert_path')
                ->info('Absolute path to the CA used to process TLS handshake')->defaultValue('')
            ->end()
            ->booleanNode('tls_verify_peer')->defaultValue(false)->end()
            ->integerNode('api_timeout')->min(Constants::API_TIMEOUT)->defaultValue(Constants::API_TIMEOUT)->end()
            ->booleanNode('use_curl')->defaultValue(false)->end()
        ->end();
    }

    /**
     * Debug settings
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     * @return void
     */
    private function addDebugNodes($rootNode)
    {
        $rootNode->children()
            ->scalarNode('forced_test_ip')->defaultValue('')->end()
            ->scalarNode('forced_test_forwarded_ip')->defaultValue('')->end()
            ->booleanNode('debug_mode')->defaultValue(false)->end()
            ->booleanNode('disable_prod_log')->defaultValue(false)->end()
            ->scalarNode('log_directory_path')->end()
            ->booleanNode('display_errors')->defaultValue(false)->end()
        ->end();
    }

    /**
     * Geolocation settings
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     * @return void
     * @throws InvalidArgumentException
     */
    private function addGeolocationNodes($rootNode)
    {
        $rootNode->children()
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
    }

    /**
     * Conditional validation
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     * @return void
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function validate($rootNode)
    {
        $rootNode->validate()
            ->ifTrue(function (array $v) {
                if ($v['auth_type'] === Constants::AUTH_KEY && empty($v['api_key'])) {
                    return true;
                }
                return false;
            })
            ->thenInvalid('Api key is required as auth type is api_key')
        ->end()
        ->validate()
            ->ifTrue(function (array $v) {
                if ($v['auth_type'] === Constants::AUTH_TLS) {
                    return empty($v['tls_cert_path']) || empty($v['tls_key_path']);
                }
                return false;
            })
            ->thenInvalid('Bouncer certificate and key paths are required for tls authentification.')
        ->end()
        ->validate()
            ->ifTrue(function (array $v) {
                if ($v['auth_type'] === Constants::AUTH_TLS && $v['tls_verify_peer'] === true) {
                    return empty($v['tls_ca_cert_path']);
                }

                return false;
            })
            ->thenInvalid('CA path is required for tls authentification with verify_peer.')
        ->end();
    }
}
