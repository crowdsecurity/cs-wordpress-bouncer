<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\CacheStorage;

use CrowdSec\RemediationEngine\Configuration\Cache\PhpFiles as PhpFilesCacheConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Config\Definition\Processor;

class PhpFiles extends AbstractCache
{
    /**
     * @throws CacheStorageException
     */
    public function __construct(array $configs, LoggerInterface $logger = null)
    {
        $this->configure($configs);
        try {
            $adapter = new TagAwareAdapter(
                new PhpFilesAdapter('', 0, $this->configs['fs_cache_path'])
            );
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw new CacheStorageException(
                'Error when creating to PhpFiles cache adapter:' . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
            // @codeCoverageIgnoreEnd
        }
        parent::__construct($this->configs, $adapter, $logger);
    }

    /**
     * Process and validate input configurations.
     */
    private function configure(array $configs): void
    {
        $configuration = new PhpFilesCacheConfig();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
    }
}
