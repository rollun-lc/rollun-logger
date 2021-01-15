<?php


namespace rollun\logger\Factory;


use Interop\Container\ContainerInterface;
use rollun\logger\LifeCycleToken;
use Zend\ServiceManager\Factory\FactoryInterface;

class LifeCycleTokenFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return LifeCycleToken::createFromHeaders();
    }
}
