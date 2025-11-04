<?php

namespace rollun\logger\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

class RedisStorageFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $redisHost = getenv('LOGS_REDIS_HOST');
        $redisPort = getenv('LOGS_REDIS_PORT') ?: 6379;

        // Create Redis connection DSN
        $dsn = sprintf('redis://%s:%d', $redisHost, $redisPort);

        // Create Redis adapter with PSR-6 interface
        $redisAdapter = new RedisAdapter(
            RedisAdapter::createConnection($dsn, [
                'timeout' => 1,
            ]),
            'logs_', // namespace
            86400 // default TTL: 1 day
        );

        // Convert PSR-6 to PSR-16 (SimpleCache)
        return new Psr16Cache($redisAdapter);
    }
}
