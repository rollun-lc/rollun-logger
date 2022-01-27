<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\logger\Middleware\Factory;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use rollun\logger\Middleware\RequestLoggedMiddleware;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class RequestLoggedMiddlewareFactory
 * @package rollun\logger\Middleware\Factory
 */
class RequestLoggedMiddlewareFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return object|RequestLoggedMiddleware
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $logger = $container->get(LoggerInterface::class);

        return new RequestLoggedMiddleware($logger);
    }
}
