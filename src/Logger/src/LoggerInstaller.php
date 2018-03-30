<?php


namespace rollun\logger;

use Psr\Log\LoggerInterface;
use rollun\logger\Processor\Factory\LifeCycleTokenReferenceInjectorFactory;
use rollun\logger\Processor\IdMaker;
use rollun\logger\Processor\LifeCycleTokenInjector;
use rollun\logger\Writer\Factory\HttpFactory as HttpWriterFactory;
use rollun\logger\Writer\Http as HttpWriter;
use rollun\utils\DbInstaller;
use Zend\Log\Writer\Factory\WriterFactory;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\Log\Writer\Mock as WriterMock;
use Zend\Log\LoggerAbstractServiceFactory;
use Zend\Log\LoggerServiceFactory;
use Zend\Log\FilterPluginManagerFactory;
use Zend\Log\FormatterPluginManagerFactory;
use Zend\Log\ProcessorPluginManagerFactory;
use Zend\Log\WriterPluginManagerFactory;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream as WriterStream;
use Zend\Log\Writer\Db as WriterDb;
use Zend\Log\Formatter\Simple as FormatterSimple;
use rollun\logger\Formatter\ContextToString;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Adapter\AdapterAbstractServiceFactory;
use Psr\Container\ContainerExceptionInterface;
use rollun\installer\Install\InstallerAbstract;
use rollun\installer\InstallerException;
use Zend\Db\Adapter\Adapter;

class LoggerInstaller extends InstallerAbstract
{

    protected $config = [];

    /**
     * @return boolean
     */
    protected function hasAdapters()
    {
        return !empty($this->getAdapters());
    }

    /**
     * @param bool $isAll
     * @return array
     */
    protected function getAdapters($isAll = true)
    {
        try {
            $config = $this->container->get("config");
            $adapters = isset($config["db"]["adapters"]) ? $config["db"]["adapters"] : [];
            if (!$isAll) {
                $adapters = array_filter($adapters, function ($adapter) {
                    return (isset($adapter[static::class]) && $adapter[static::class]);
                });
            }
            return $adapters;
        } catch (ContainerExceptionInterface $exception) {
            return [];
        }
    }

    /**
     * @return string
     */
    protected function selectDbAdapter()
    {
        if ($this->hasAdapters()) {
            $adaptersName = array_keys($this->getAdapters());
            $adapterNameKey = $this->consoleIO->select("Select db adapter who been used for logs", $adaptersName, array_search(AdapterInterface::class, $adaptersName));
            return $adaptersName[$adapterNameKey];
        }
        throw new InstallerException("Db adapter not found.");
    }

    /**
     * install
     * @return array
     * @throws ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function install()
    {
        //create custom (logger) db adapter
        $adapterName = $this->selectDbAdapter();
        //create unique table for logs
        $tableName = $this->createTable($adapterName);
        //create config for write in table
        $this->config = [
            'log_formatters' => [
                'factories' => [
                    ContextToString::class => InvokableFactory::class
                ],
            ],
            'log_filters' => [
                'factories' => [
// ...
                ],
            ],
            'log_processors' => [
                'factories' => [
                    IdMaker::class => InvokableFactory::class,
                    LifeCycleTokenInjector::class => LifeCycleTokenReferenceInjectorFactory::class,
                ],
            ],
            'log_writers' => [
                'factories' => [
                    HttpWriter::class => WriterFactory::class
                ],
            ],
            'dependencies' => [
                'abstract_factories' => [
                    LoggerAbstractServiceFactory::class,
                    AdapterAbstractServiceFactory::class,
                ],
                'factories' => [
                    Logger::class => LoggerServiceFactory::class,
                    'LogFilterManager' => FilterPluginManagerFactory::class,
                    'LogFormatterManager' => FormatterPluginManagerFactory::class,
                    'LogProcessorManager' => ProcessorPluginManagerFactory::class,
                    'LogWriterManager' => WriterPluginManagerFactory::class,
                ],
                'aliases' => [],
            ],
            'log' => [
                LoggerInterface::class => [
                    'processors' => [
                        ['name' => IdMaker::class],
                        ['name' => LifeCycleTokenInjector::class],
                    ],
                    'writers' => [
                        [
                            'name' => WriterDb::class,
                            'options' => [
                                'db' => $adapterName,
                                'table' => $tableName,
                                'column' => [
                                    'id' => 'id',
                                    'timestamp' => 'timestamp',
                                    'message' => 'message',
                                    'level' => 'level',
                                    'priority' => 'priority',
                                    'context' => 'context',
                                ],
                                'formatter' => ContextToString::class
                            ],

                        ],
                        [
                            'name' => HttpWriter::class,
                            'options' => [
                                'formatter' => ContextToString::class
                            ],
                        ],
                    ],
                ]
            ]
        ];
        return $this->config;
    }

    /**
     * Clean all installation
     * @return void
     */
    public function uninstall()
    {

    }

    /**
     * Return true if install, or false else
     * @return bool
     */
    public function isInstall()
    {
        return $this->container->has(LoggerInterface::class);
    }

    /**
     * Return string with description of installable functional.
     * @param string $lang ; set select language for description getted.
     * @return string
     */
    public function getDescription($lang = "en")
    {
        return "Install logger in system";
    }

    public function getDependencyInstallers()
    {
        return [
            DbInstaller::class
        ];
    }


    /**
     * @param $adapterName
     * @return string
     * @throws ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function createTable($adapterName)
    {
        /** @var Adapter $adapter */
        $adapter = $this->container->get($adapterName);
        $serviceName = $this->consoleIO->ask("Set application service name: ", "");
        $serviceName = preg_replace('/([^\w\d\_]+)/', '_', $serviceName);
        $tableName = "logs_$serviceName";
        $adapter->query("CREATE TABLE `$tableName` (" .
            "`id` varchar(255) NOT NULL," .
            "`timestamp` varchar(32) NOT NULL," .
            "`level` varchar(32) NOT NULL," .
            "`priority` int(11) NOT NULL," .
            "`message` text NOT NULL," .
            "`context` text NOT NULL," .
            "`token` varchar(32) NOT NULL," .
            "`parent_token` varchar(32)," .
            "FOREIGN KEY (`parent_token`) REFERENCES logs(token)," .
            "PRIMARY KEY (`id`)" .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            , Adapter::QUERY_MODE_EXECUTE);
        return $tableName;
    }
}