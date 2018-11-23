<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\logger\Factory;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use rollun\logger\LoggingErrorListener;

class LoggingErrorListenerDelegatorFactory
{
    /**
     * @param ContainerInterface $container
     * @param string $name
     * @param callable $callback
     * @return mixed
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback)
    {
        $logger = $container->get(LoggerInterface::class);

        $listener = new LoggingErrorListener($logger);
        $errorHandler = $callback();
        $errorHandler->attachListener($listener);
        return $errorHandler;
    }
}
