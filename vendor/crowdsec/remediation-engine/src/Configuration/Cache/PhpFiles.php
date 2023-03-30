<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Configuration\Cache;

use CrowdSec\RemediationEngine\Configuration\AbstractCache;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * The remediation cache configuration for PhpFiles.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class PhpFiles extends AbstractCache
{
    /**
     * @var string[]
     */
    protected $keys = [
        'fs_cache_path',
        'use_cache_tags',
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('config');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->scalarNode('fs_cache_path')->isRequired()->cannotBeEmpty()->end()
        ->end();
        $this->addCommonNodes($rootNode);

        return $treeBuilder;
    }
}
