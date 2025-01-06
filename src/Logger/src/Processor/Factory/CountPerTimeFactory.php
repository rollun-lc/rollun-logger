<?php


namespace rollun\logger\Processor\Factory;


use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use rollun\logger\Processor\CountPerTime;

class CountPerTimeFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!isset($options['parent_name'])) {
            throw new \InvalidArgumentException("Missing 'parent_name' option");
        }

        $processorPluginsManager = $container->get('LogProcessorManager');

        $onTrue = [];

        foreach ($options['onTrue'] ?? [] as $item) {
            $onTrue[] = $processorPluginsManager->get($item['name'], $item['options'] ?? []);
        }

        $options['onTrue'] = $onTrue;

        $onFalse = [];

        foreach ($options['onFalse'] ?? [] as $item) {
            $onFalse[] = $processorPluginsManager->get($item['name'], $item['options'] ?? []);
        }

        $options['onFalse'] = $onFalse;

        return new CountPerTime(
            $container->get('StorageForLogsCount'),
            $options['parent_name'],
            $options
        );
    }

}
