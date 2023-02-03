<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\CacheStorage;

use CrowdSec\RemediationEngine\Configuration\Cache\Redis as RedisCacheConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\Config\Definition\Processor;

class Redis extends AbstractCache
{
    /**
     * @throws CacheStorageException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(array $configs, LoggerInterface $logger = null)
    {
        $this->configure($configs);

        try {
            $adapter = new RedisTagAwareAdapter(RedisAdapter::createConnection($this->configs['redis_dsn']));
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw new CacheStorageException(
                'Error when creating Redis cache adapter:' . $e->getMessage(),
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
        $configuration = new RedisCacheConfig();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
    }
}
