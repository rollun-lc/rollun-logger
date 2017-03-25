<?php

namespace rollun\logger\Factory;
use Interop\Container\ContainerInterface;
use rollun\logger\Logger;
use rollun\logger\LoggingErrorListener;

/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 25.03.17
 * Time: 11:22 AM
 */
class LoggingErrorListenerDelegatorFactory
{
    public function __invoke(ContainerInterface $container, $name, $callback)
    {
        $listener = new LoggingErrorListener($container->get(Logger::DEFAULT_LOGGER_SERVICE));
        $errorHandler = $callback();
        $errorHandler->attachListener($listener);
        return $errorHandler;
    }
}