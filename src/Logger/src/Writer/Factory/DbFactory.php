<?php

namespace rollun\logger\Writer\Factory;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use rollun\logger\Writer\Db;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Db writer factory
 * This factory configuring with incoming $options param
 *
 * Class DbFactory
 * @package Zend\Log\Writer\Factory
 */
class DbFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * Expected top keys are:
     * - db,         required (db adapter service name)
     * - table,      optional
     * - column,     optional
     * - separator,  optional
     *
     * @return object|Db
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!isset($options['db'])) {
            throw new InvalidArgumentException("Missing 'db' option");
        }

        $options['db'] = $container->get($options['db']);
        $options = $this->populateOptions($options, $container, 'filter_manager', 'LogFilterManager');
        $options = $this->populateOptions($options, $container, 'formatter_manager', 'LogFormatterManager');

        return new Db($options);
    }

    /**
     * Populates the options array with the correct container value.
     *
     * @param array $options
     * @param ContainerInterface $container
     * @param string $name
     * @param string $defaultService
     * @return array
     */
    private function populateOptions(array $options, ContainerInterface $container, $name, $defaultService)
    {
        if (isset($options[$name]) && is_string($options[$name])) {
            $options[$name] = $container->get($options[$name]);
            return $options;
        }

        if (! isset($options[$name]) && $container->has($defaultService)) {
            $options[$name] = $container->get($defaultService);
            return $options;
        }

        return $options;
    }
}
