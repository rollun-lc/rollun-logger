<?php

namespace rollun\logger\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * @deprecated since version 7.8.0, will be removed in version 8.0.0.
 * Reason: CountPerTime functionality and related laminas-cache dependency are no longer used and create unnecessary dependencies.
 */
class RedisStorageFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        trigger_error(
            'Class RedisStorageFactory is deprecated since version 7.8.0 and will be removed in version 8.0.0. CountPerTime functionality and related laminas-cache dependency are no longer used.',
            E_USER_DEPRECATED
        );

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
