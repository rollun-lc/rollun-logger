<?php


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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback)
    {
        $listener = new LoggingErrorListener($container->get(LoggerInterface::class));
        $errorHandler = $callback();
        $errorHandler->attachListener($listener);
        return $errorHandler;
    }
}