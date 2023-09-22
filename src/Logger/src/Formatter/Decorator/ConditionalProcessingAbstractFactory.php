<?php


namespace rollun\logger\Formatter\Decorator;


use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class ConditionalProcessingAbstractFactory implements AbstractFactoryInterface
{
    public const KEY = self::class;

    public const KEY_WRITER = 'writer';

    public const KEY_CONFIG = 'config';

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

        $writer = $container->get($config[self::KEY_WRITER]);
        $serviceConfig = [];

        foreach ($config[self::KEY_CONFIG] as $configItem) {
            $serviceConfig[] = $container->get($configItem);
        }

        return new ConditionalProcessing($writer, $serviceConfig);
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
