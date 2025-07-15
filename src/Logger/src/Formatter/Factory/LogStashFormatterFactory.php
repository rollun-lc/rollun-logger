<?php

namespace rollun\logger\Formatter\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use rollun\logger\Formatter\LogStashFormatter;
use rollun\logger\Services\RecursiveJsonTruncator;

class LogStashFormatterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new LogStashFormatter(
            getenv("LOGSTASH_INDEX"),
            [
                'timestamp'              => '@timestamp',
                'message'                => 'message',
                'level'                  => 'level',
                'priority'               => 'priority',
                'context'                => 'context',
                'lifecycle_token'        => 'lifecycle_token',
                'parent_lifecycle_token' => 'parent_lifecycle_token',
                '_index_name'            => '_index_name',
            ],
            $container->get(RecursiveJsonTruncator::class),
        );
    }
}
