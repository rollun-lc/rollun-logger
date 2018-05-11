<?php


namespace rollun\logger\Factory;


use Interop\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;
use rollun\logger\LoggingErrorListener;
use rollun\logger\SimpleLogger;

class LoggingErrorListenerDelegatorFactory
{
    /**
     * @param ContainerInterface $container
     * @param string $name
     * @param callable $callback
     * @return mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
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