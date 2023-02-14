<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine;

use CrowdSec\CapiClient\ClientException;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\RemediationEngine\CacheStorage\AbstractCache;
use CrowdSec\RemediationEngine\CacheStorage\CacheStorageException;
use CrowdSec\RemediationEngine\Configuration\Capi as CapiRemediationConfig;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;

class CapiRemediation extends AbstractRemediation
{
    /** @var array The list of each known CAPI remediation, sorted by priority */
    public const ORDERED_REMEDIATIONS = [Constants::REMEDIATION_BAN];
    /**
     * @var Watcher
     */
    private $client;

    public function __construct(
        array $configs,
        Watcher $client,
        AbstractCache $cacheStorage,
        LoggerInterface $logger = null
    ) {
        // Force stream mode for CAPI remediation
        $configs['stream_mode'] = true;
        $this->configure($configs);
        $this->client = $client;
        parent::__construct($this->configs, $cacheStorage, $logger);
    }

    public function getClient(): Watcher
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     *
     * @throws CacheStorageException
     * @throws InvalidArgumentException
     * @throws RemediationException|CacheException
     */
    public function getIpRemediation(string $ip): string
    {
        $cachedDecisions = $this->getAllCachedDecisions($ip, $this->getCountryForIp($ip));

        if (!$cachedDecisions) {
            $this->logger->debug('There is no cached decision', [
                'type' => 'CAPI_REM_NO_CACHED_DECISIONS',
                'ip' => $ip,
            ]);
            // As CAPI is always in stream_mode, we do not store this bypass
            return Constants::REMEDIATION_BYPASS;
        }

        return $this->getRemediationFromDecisions($cachedDecisions);
    }

    /**
     * {@inheritdoc}
     *
     * @throws CacheStorageException
     * @throws InvalidArgumentException
     * @throws CacheException|ClientException
     */
    public function refreshDecisions(): array
    {
        $rawDecisions = $this->client->getStreamDecisions();
        $newDecisions = $this->convertRawDecisionsToDecisions($rawDecisions[self::CS_NEW] ?? []);
        $deletedDecisions = $this->convertRawDecisionsToDecisions($rawDecisions[self::CS_DEL] ?? []);

        $stored = $this->storeDecisions($newDecisions);
        $removed = $this->removeDecisions($deletedDecisions);

        return [
            self::CS_NEW => $stored[AbstractCache::DONE] ?? 0,
            self::CS_DEL => $removed[AbstractCache::DONE] ?? 0,
        ];
    }

    /**
     * Process and validate input configurations.
     */
    private function configure(array $configs): void
    {
        $configuration = new CapiRemediationConfig();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
    }
}
