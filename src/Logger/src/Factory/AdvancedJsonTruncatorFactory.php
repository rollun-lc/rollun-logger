<?php

namespace rollun\logger\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use rollun\logger\Services\AdvancedJsonTruncator;

class AdvancedJsonTruncatorFactory implements FactoryInterface
{
    private const DEFAULT_PARAMS = [
        'limit' => 1000,
        'depthLimit' => 3,
        'maxArrayChars' => 1000,
        'arrayLimit' => 3,
        ];

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new AdvancedJsonTruncator(self::DEFAULT_PARAMS);
    }
}