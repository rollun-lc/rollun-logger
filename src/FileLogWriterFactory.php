<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 10.01.17
 * Time: 10:30
 */

namespace zaboy\logger;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class FileLogWriterFactory implements FactoryInterface
{

    const FILE_NAME_KEY = 'file';
    const DELIMITER_KEY = 'delimiter';
    const END_STRING_KEY = 'endString';

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
        if ($container->has("config")) {
            $config = $container->get("config");
            if (isset($config['logWriter'][FileLogWriter::class])) {
                $config = $config['logWriter'][$requestedName];

                $reflectionConstruct = new \ReflectionMethod(FileLogWriter::class, '__construct');
                $params = $reflectionConstruct->getParameters();

                $file = '';
                $delimiter = '';
                $endString = '';
                foreach ($params as $param) {
                    /** @var $param \ReflectionParameter */
                    ${$param->getName()} = isset($config[$param->getName()]) ? $config[$param->getName()]
                        : $param->getDefaultValue();
                    if ($param->getName() == static::FILE_NAME_KEY && !file_exists($file)) {
                        $file = $param->getDefaultValue();
                    }
                }
                return new FileLogWriter($file, $delimiter, $endString);
            }
        }
        return new FileLogWriter();
    }
}
