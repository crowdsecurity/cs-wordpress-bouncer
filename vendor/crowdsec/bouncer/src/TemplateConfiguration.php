<?php

namespace CrowdSecBouncer;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The template configuration. You'll be able to configure text and colors of the captcha wall and the ban wall.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class TemplateConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $defaultSublitle = 'This page is protected against cyber attacks and your IP has been banned by our system.';
        $treeBuilder = new TreeBuilder('config');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('color')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('text')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('primary')->defaultValue('black')->end()
                                ->scalarNode('secondary')->defaultValue('#AAA')->end()
                                ->scalarNode('button')->defaultValue('white')->end()
                                ->scalarNode('error_message')->defaultValue('#b90000')->end()
                            ->end()
                        ->end()
                        ->arrayNode('background')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('page')->defaultValue('#eee')->end()
                                ->scalarNode('container')->defaultValue('white')->end()
                                ->scalarNode('button')->defaultValue('#626365')->end()
                                ->scalarNode('button_hover')->defaultValue('#333')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('text')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('captcha_wall')
                            ->addDefaultsIfNotSet()
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
                        ->arrayNode('ban_wall')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('tab_title')->defaultValue('Oops..')->end()
                                ->scalarNode('title')->defaultValue('🤭 Oh!')->end()
                                ->scalarNode('subtitle')->defaultValue($defaultSublitle)->end()
                                ->scalarNode('footer')->defaultValue('')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('hide_crowdsec_mentions')->defaultValue(false)->end()
                ->scalarNode('custom_css')->defaultValue(null)->end()
            ->end();

        return $treeBuilder;
    }
}
