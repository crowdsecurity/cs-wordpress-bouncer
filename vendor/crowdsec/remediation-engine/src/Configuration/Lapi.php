<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Configuration;

use CrowdSec\RemediationEngine\Constants;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * The Lapi remediation configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Lapi extends AbstractRemediation
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
        'appsec_fallback_remediation',
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('config');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $this->addCommonNodes($rootNode);
        $this->validateCommon($rootNode);
        $this->addAppSecNodes($rootNode);
        $this->validateAppSec($rootNode);

        return $treeBuilder;
    }

    /**
     * AppSec related settings.
     *
     * @return void
     */
    private function addAppSecNodes($rootNode)
    {
        $rootNode->children()
            ->scalarNode('appsec_fallback_remediation')
                ->defaultValue(Constants::REMEDIATION_CAPTCHA)
            ->end()
        ->end();
    }

    /**
     * Conditional validation.
     *
     * @return void
     */
    protected function validateAppSec($rootNode)
    {
        $rootNode->validate()
            ->ifTrue(function (array $v) {
                return Constants::REMEDIATION_BYPASS !== $v['appsec_fallback_remediation']
                       && !in_array($v['appsec_fallback_remediation'], $v['ordered_remediations']);
            })
            ->thenInvalid('AppSec fallback remediation must belong to ordered remediations.')
        ->end();
    }
}
