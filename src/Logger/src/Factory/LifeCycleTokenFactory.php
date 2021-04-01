<?php


namespace rollun\logger\Factory;


use Interop\Container\ContainerInterface;
use rollun\logger\LifeCycleToken;
use Zend\ServiceManager\Factory\FactoryInterface;

class LifeCycleTokenFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (php_sapi_name() === 'cli') {
            return LifeCycleToken::createFromArgv();
        }
        return LifeCycleToken::createFromHeaders();
    }
}
