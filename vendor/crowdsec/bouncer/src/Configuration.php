<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

use CrowdSec\Common\Configuration\AbstractConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

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
class Configuration extends AbstractConfiguration
{
    /**
     * @var string[]
     */
    protected $keys = [
        'use_curl',
        'forced_test_ip',
        'forced_test_forwarded_ip',
        'debug_mode',
        'disable_prod_log',
        'log_directory_path',
        'display_errors',
        'cache_system',
        'captcha_cache_duration',
        'excluded_uris',
        'trust_ip_forward_array',
        'bouncing_level',
        'hide_mentions',
        'custom_css',
        'color',
        'text',
    ];

    /**
     * @throws \InvalidArgumentException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('config');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $this->addConnectionNodes($rootNode);
        $this->addDebugNodes($rootNode);
        $this->addBouncerNodes($rootNode);
        $this->addCacheNodes($rootNode);
        $this->addTemplateNodes($rootNode);

        return $treeBuilder;
    }

    /**
     * Bouncer settings.
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function addBouncerNodes($rootNode)
    {
        $rootNode->children()
            ->enumNode('bouncing_level')
                ->values(
                    [
                        Constants::BOUNCING_LEVEL_DISABLED,
                        Constants::BOUNCING_LEVEL_NORMAL,
                        Constants::BOUNCING_LEVEL_FLEX,
                    ]
                )
                ->defaultValue(Constants::BOUNCING_LEVEL_NORMAL)
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
     * Cache settings.
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function addCacheNodes($rootNode)
    {
        $rootNode->children()
            ->enumNode('cache_system')
                ->values(
                    [
                        Constants::CACHE_SYSTEM_PHPFS,
                        Constants::CACHE_SYSTEM_REDIS,
                        Constants::CACHE_SYSTEM_MEMCACHED,
                    ]
                )
                ->defaultValue(Constants::CACHE_SYSTEM_PHPFS)
            ->end()
            ->integerNode('captcha_cache_duration')
                ->min(1)->defaultValue(Constants::CACHE_EXPIRATION_FOR_CAPTCHA)
            ->end()
        ->end();
    }

    /**
     * LAPI connection settings.
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     *
     * @return void
     */
    private function addConnectionNodes($rootNode)
    {
        $rootNode->children()
            ->booleanNode('use_curl')->defaultValue(false)->end()
        ->end();
    }

    /**
     * Debug settings.
     *
     * @param NodeDefinition|ArrayNodeDefinition $rootNode
     *
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
     * @return void
     */
    private function addTemplateNodes($rootNode)
    {
        $defaultSubtitle = 'This page is protected against cyber attacks and your IP has been banned by our system.';
        $rootNode->children()
            ->arrayNode('color')->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('text')->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('primary')->defaultValue('black')->end()
                            ->scalarNode('secondary')->defaultValue('#AAA')->end()
                            ->scalarNode('button')->defaultValue('white')->end()
                            ->scalarNode('error_message')->defaultValue('#b90000')->end()
                        ->end()
                    ->end()
                    ->arrayNode('background')->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('page')->defaultValue('#eee')->end()
                            ->scalarNode('container')->defaultValue('white')->end()
                            ->scalarNode('button')->defaultValue('#626365')->end()
                            ->scalarNode('button_hover')->defaultValue('#333')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('text')->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('captcha_wall')->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('tab_title')->defaultValue('Oops..')->end()
                            ->scalarNode('title')->defaultValue('Hmm, sorry but...')->end()
                            ->scalarNode('subtitle')->defaultValue('Please complete the security check.')->end()
                            ->scalarNode('refresh_image_link')->defaultValue('refresh image')->end()
                            ->scalarNode('captcha_placeholder')->defaultValue('Type here...')->end()
                            ->scalarNode('send_button')->defaultValue('CONTINUE')->end()
                            ->scalarNode('error_message')->defaultValue('Please try again.')->end()
                            ->scalarNode('footer')->defaultValue('')->end()
                        ->end()
                    ->end()
                    ->arrayNode('ban_wall')->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('tab_title')->defaultValue('Oops..')->end()
                            ->scalarNode('title')->defaultValue('🤭 Oh!')->end()
                            ->scalarNode('subtitle')->defaultValue($defaultSubtitle)->end()
                            ->scalarNode('footer')->defaultValue('')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->booleanNode('hide_mentions')->defaultValue(false)->end()
            ->scalarNode('custom_css')->defaultValue('')->end()
        ->end();
    }
}
