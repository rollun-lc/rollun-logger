<?php

namespace rollun\logger\Factory;

use Interop\Container\ContainerInterface;
use Zend\Cache\StorageFactory;
use Zend\ServiceManager\Factory\FactoryInterface;

class RedisStorageFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return StorageFactory::factory([
            'adapter' => [
                'name' => 'redis',
                'options' => [
                    'ttl' => 86400, // 1 day
                    'server' => [
                        'host' => getenv('LOGS_REDIS_HOST'),
                        'port' => getenv('LOGS_REDIS_PORT') ?: 6379,
                        'timeout' => 1,
                    ]
                ]
            ],
        ]);
    }
}