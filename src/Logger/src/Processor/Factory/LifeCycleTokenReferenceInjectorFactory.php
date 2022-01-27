<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\logger\Processor\Factory;

use Interop\Container\ContainerInterface;
use rollun\logger\Processor\LifeCycleTokenInjector;
use rollun\logger\LifeCycleToken;
use Laminas\ServiceManager\Factory\FactoryInterface;

class LifeCycleTokenReferenceInjectorFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return object|LifeCycleTokenInjector
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $token = $container->get(LifeCycleToken::class);

        return new LifeCycleTokenInjector($token);
    }
}
