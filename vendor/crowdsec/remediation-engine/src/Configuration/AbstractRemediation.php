<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Configuration;

use CrowdSec\Common\Configuration\AbstractConfiguration;
use CrowdSec\RemediationEngine\CapiRemediation;
use CrowdSec\RemediationEngine\Constants;
use CrowdSec\RemediationEngine\LapiRemediation;

/**
 * The remediation common configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractRemediation extends AbstractConfiguration
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
    ];

    private function getDefaultOrderedRemediations(): array
    {
        if (Capi::class === get_class($this)) {
            return array_merge(CapiRemediation::ORDERED_REMEDIATIONS, [Constants::REMEDIATION_BYPASS]);
        }

        return array_merge(LapiRemediation::ORDERED_REMEDIATIONS, [Constants::REMEDIATION_BYPASS]);
    }

    /**
     * Common remediation settings.
     *
     * @return void
     */
    protected function addCommonNodes($rootNode)
    {
        $rootNode->children()
            ->scalarNode('fallback_remediation')
                ->defaultValue(Constants::REMEDIATION_BYPASS)
            ->end()
            ->arrayNode('ordered_remediations')->cannotBeEmpty()
                ->validate()
                ->ifArray()
                ->then(function (array $remediations) {
                    // Remove bypass if any
                    foreach ($remediations as $key => $remediation) {
                        if (Constants::REMEDIATION_BYPASS === $remediation) {
                            unset($remediations[$key]);
                        }
                    }
                    // Add bypass as the lowest priority remediation
                    $remediations = array_merge($remediations, [Constants::REMEDIATION_BYPASS]);

                    return array_values(array_unique($remediations));
                })
                ->end()
                ->scalarPrototype()->cannotBeEmpty()->end()
                ->defaultValue($this->getDefaultOrderedRemediations())
            ->end()
            ->booleanNode('stream_mode')->defaultTrue()->end()
            ->integerNode('clean_ip_cache_duration')
                ->min(1)->defaultValue(Constants::CACHE_EXPIRATION_FOR_CLEAN_IP)
            ->end()
            ->integerNode('bad_ip_cache_duration')
                ->min(1)->defaultValue(Constants::CACHE_EXPIRATION_FOR_BAD_IP)
            ->end()
        ->end();
        $this->addGeolocationNodes($rootNode);
    }

    /**
     * Geolocation settings.
     *
     * @return void
     */
    private function addGeolocationNodes($rootNode)
    {
        $rootNode->children()
            ->arrayNode('geolocation')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enabled')
                        ->defaultFalse()
                    ->end()
                    ->integerNode('cache_duration')
                        ->min(0)->defaultValue(Constants::CACHE_EXPIRATION_FOR_GEO)
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
     * Conditional validation.
     *
     * @return void
     */
    protected function validateCommon($rootNode)
    {
        $rootNode->validate()
                ->ifTrue(function (array $v) {
                    return Constants::REMEDIATION_BYPASS !== $v['fallback_remediation']
                           && !in_array($v['fallback_remediation'], $v['ordered_remediations']);
                })
                ->thenInvalid('Fallback remediation must belong to ordered remediations.')
            ->end();
    }
}
