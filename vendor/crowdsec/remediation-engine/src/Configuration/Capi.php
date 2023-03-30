<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Configuration;

use CrowdSec\RemediationEngine\Constants;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * The Capi remediation configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Capi extends AbstractRemediation
{
    /**
     * @var string[]
     */
    protected $keys = [
        'fallback_remediation',
        'ordered_remediations',
        'stream_mode',
        'clean_ip_cache_duration',
        'bad_ip_cache_duration',
        'geolocation',
        'refresh_frequency_indicator',
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('config');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $this->addCommonNodes($rootNode);
        $this->validateCommon($rootNode);
        $this->addCapiNodes($rootNode);

        return $treeBuilder;
    }

    /**
     * Common remediation settings.
     *
     * @return void
     */
    private function addCapiNodes($rootNode)
    {
        $rootNode->children()
            ->integerNode('refresh_frequency_indicator')
                ->min(1)->defaultValue(Constants::REFRESH_FREQUENCY)
            ->end()
        ->end();
    }
}
