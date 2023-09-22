<?php


namespace rollun\logger\Formatter\Decorator;


use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class ConditionalProcessingConfigAbstractFactory implements AbstractFactoryInterface
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

        foreach ($config[self::KEY_FILTERS] as $filter) {
            $filters[] = $filterPluginsManager->get($filter);
        }

        $processors = [];

        foreach ($config[self::KEY_PROCESSORS] as $processor) {
            $processors[] = $processorPluginsManager->get($processor);
        }

        return new ConditionalProcessingConfig($requestedName, $filters, $processors);
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
