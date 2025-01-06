<?php

namespace rollun\test\logger;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class SimpleArrayContainer implements ContainerInterface
{
    public function __construct(
        private array $container
    ) {}

    public function get(string $id)
    {
        return $this->container[$id] ??
            throw new class ("$id is not set") extends Exception implements ContainerExceptionInterface {};
    }

    public function has(string $id): bool
    {
        return isset($this->container[$id]);
    }
}