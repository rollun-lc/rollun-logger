<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 10.01.17
 * Time: 10:30
 */

namespace rollun\logger\LogWriter;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\installer\Command;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class FileLogWriterFactory implements FactoryInterface
{

    const LOGS_DIR = 'logs';

    const LOGS_FILE_NAME = 'logs.csv';

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new FileLogWriter(static::getLogFile());
    }

    static public function getLogFile()
    {
        return realpath(Command::getDataDir() . DIRECTORY_SEPARATOR . static::LOGS_DIR . DIRECTORY_SEPARATOR . static::LOGS_FILE_NAME);
    }
}
