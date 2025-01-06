<?php


namespace rollun\logger\Processor\Factory;


use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

class ProcessorAbstractFactory implements AbstractFactoryInterface
{
    public const KEY = self::class;

    public function canCreate(ContainerInterface $container, $requestedName): bool
    {
        try {
            $config = $this->getConfig($container);
        } catch (\RuntimeException $e) {
            return false;
        }

        return isset($config[$requestedName]);
    }

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $this->getConfig($container);
        $config = $config[$requestedName];

        if (!isset($config['name'])) {
            throw new \RuntimeException("Invalid config for '$requestedName'");
        }

        $processorPluginsManager = $container->get('LogProcessorManager');

        return $processorPluginsManager->get($config['name'], $config['options'] ?? []);
    }

    protected function getConfig(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config[self::KEY])) {
            throw new \RuntimeException("Config for '" . self::KEY . "' not found");
        }

        return $config[self::KEY];
    }

}
