<?php

namespace rollun\logger\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use rollun\logger\Services\RecursiveJsonTruncator;
use rollun\logger\Services\RecursiveTruncationParamsValueObject;

class RecursiveJsonTruncatorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = RecursiveTruncationParamsValueObject::createFromArray(
            $container->get('config')[self::class]
        );
        return new RecursiveJsonTruncator($config);
    }
}