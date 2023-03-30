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
            $fileAdapter = new PhpFilesAdapter('', 0, $this->configs['fs_cache_path']);
            $adapter = !empty($this->configs['use_cache_tags']) ? new TagAwareAdapter($fileAdapter) : $fileAdapter;
            if ($logger) {
                $adapter->setLogger($logger);
            }
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            $message = 'Error when creating to PhpFiles cache adapter:' . $e->getMessage();
            throw new CacheStorageException($message, (int) $e->getCode(), $e);
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
