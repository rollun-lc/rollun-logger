<?php


namespace rollun\logger\Processor\Factory;


use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use rollun\logger\Processor\ConditionalProcessor;

class ConditionalProcessorAbstractFactory implements AbstractFactoryInterface
{
    public const KEY = self::class;

    public const KEY_FILTERS = 'filters';

    public const KEY_PROCESSORS = 'processors';

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

        $filterPluginsManager = $container->get('LogFilterManager');
        $processorPluginsManager = $container->get('LogProcessorManager');

        $filters = [];

        foreach ($config[self::KEY_FILTERS] as $filterConfig) {
            if (!isset($filterConfig['name'])) {
                throw new \RuntimeException("Invalid config for '$requestedName'");
            }
            $filters[] = $filterPluginsManager->get($filterConfig['name'], $filterConfig['options'] ?? []);
        }

        $processors = [];

        foreach ($config[self::KEY_PROCESSORS] as $processorConfig) {
            if (!isset($processorConfig['name'])) {
                throw new \RuntimeException("Invalid config for '$requestedName'");
            }
            $options = $processorConfig['options'] ?? [];
            $options['parent_name'] = $requestedName;
            $processors[] = $processorPluginsManager->get($processorConfig['name'], $options);
        }

        return new ConditionalProcessor($filters, $processors);
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
