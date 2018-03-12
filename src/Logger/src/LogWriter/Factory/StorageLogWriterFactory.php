<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 02.06.17
 * Time: 14:51
 */

namespace rollun\logger\LogWriter\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\logger\LogWriter\DataStoreLogWriter;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class StorageLogWriterFactory implements FactoryInterface
{
    const KEY = 'keyStorageLogWriter';

    const KEY_STORAGE_SERVICE = 'keyStorageService';

    /**
     * Create an object
     * StorageLogWriterFactory::KEY => [
     *      StorageLogWriterFactory::KEY_STORAGE_SERVICE => 'loggerStorage'
     * ]
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
        if(empty($options)) {
            $config = $container->get('config');
            $serviceConfig = $config[static::KEY];
        } else {
            $serviceConfig = $options;
        }
        $storageService = $container->get($serviceConfig[static::KEY_STORAGE_SERVICE]);
        return new DataStoreLogWriter($storageService);
    }
}
