<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient;

use CrowdSec\LapiClient\Configuration\Metrics as PropertiesConfig;
use CrowdSec\LapiClient\Configuration\Metrics\Items as ItemsConfig;
use CrowdSec\LapiClient\Configuration\Metrics\Meta as MetaConfig;
use Symfony\Component\Config\Definition\Processor;

/**
 * The Metrics class.
 *
 * @author    CrowdSec team
 *
 * @see     https://crowdsec.net CrowdSec Official Website
 * @see     https://docs.crowdsec.net/docs/next/observability/usage_metrics/
 * @see     https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=LAPI#/Remediation%20component/usage-metrics
 *
 * @copyright Copyright (c) 2024+ CrowdSec
 * @license   MIT License
 */
class Metrics
{
    /**
     * @var array
     */
    private $items;
    /**
     * @var array
     */
    private $meta;
    /**
     * @var array
     */
    private $properties;

    public function __construct(
        array $properties,
        array $meta,
        array $items = []
    ) {
        $this->configureProperties($properties);
        $this->configureMeta($meta);
        $this->configureItems($items);
    }

    public function toArray(): array
    {
        return [
            'remediation_components' => [
                $this->properties +
                [
                    'metrics' => [
                        [
                            'meta' => $this->meta,
                            'items' => $this->items,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function configureItems(array $items): void
    {
        $configuration = new ItemsConfig();
        $processor = new Processor();
        $this->items = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($items)]);
    }

    private function configureMeta(array $meta): void
    {
        $configuration = new MetaConfig();
        $processor = new Processor();
        $this->meta = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($meta)]);
    }

    private function configureProperties(array $properties): void
    {
        $configuration = new PropertiesConfig();
        $processor = new Processor();
        $this->properties = $processor->processConfiguration(
            $configuration,
            [$configuration->cleanConfigs($properties)]
        );
    }
}
