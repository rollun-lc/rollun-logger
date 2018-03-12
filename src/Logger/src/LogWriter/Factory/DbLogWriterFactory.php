<?php

namespace rollun\logger\LogWriter\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\Db\TableGateway\TableGateway;
use rollun\logger\LogWriter\DbLogWriter;

/**
 *
 * Table 'logs' has to present in db with filds 'id', 'level' and 'message'
 * 
 * CREATE TABLE `logs_db`.`logs` (
 *  `id` VARCHAR(255) NOT NULL ,
 *  `level` VARCHAR(32) NOT NULL ,
 *  `message` TEXT NOT NULL ,
 *   PRIMARY KEY (`id`)
 * ) ENGINE = InnoDB;
 *
 * @see DbLogWriter
 */
class DbLogWriterFactory implements FactoryInterface
{

    const LOG_TABLE_NAME = 'logs';
    const DEFAULT_DB_ADAPTER = 'db';
    const LOG_DB_ADAPTER = 'logDb';

    /**
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
        $dbAdapter = $container->has(static::LOG_DB_ADAPTER) ?
                $container->get(static::LOG_DB_ADAPTER) :
                ($container->has(static::DEFAULT_DB_ADAPTER) ? $container->get(static::DEFAULT_DB_ADAPTER) : null );

        if (is_null($dbAdapter)) {
            throw new \RuntimeException("db adapter for logger not found!", 0);
        }
        try {
            $tableGateway = new TableGateway(static::LOG_TABLE_NAME, $dbAdapter);
        } catch (\Exception $e) {
            throw new \RuntimeException(
            'Can\'t init  TableGateway for table: ' . static::LOG_TABLE_NAME, 0, $e
            );
        }
        return new DbLogWriter($tableGateway);
    }

}
