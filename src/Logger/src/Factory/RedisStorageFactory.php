<?php

namespace rollun\logger\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RedisStorageFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var StorageAdapterFactoryInterface $storageFactory */
        $storageFactory = $container->get(StorageAdapterFactoryInterface::class);
        return $storageFactory->create('redis', [
            'ttl' => 86400, // 1 day
            'server' => [
                'host' => getenv('LOGS_REDIS_HOST'),
                'port' => getenv('LOGS_REDIS_PORT') ?: 6379,
                'timeout' => 1,
            ],
        ]);
    }
}
