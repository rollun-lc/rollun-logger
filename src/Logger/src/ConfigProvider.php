<?php


namespace rollun\logger;

use Psr\Log\LoggerInterface;
use Zend\Log\LoggerAbstractServiceFactory;
use Zend\Log\LoggerServiceFactory;
use Zend\Log\FilterPluginManagerFactory;
use Zend\Log\FormatterPluginManagerFactory;
use Zend\Log\ProcessorPluginManagerFactory;
use Zend\Log\WriterPluginManagerFactory;
use Zend\Log\Logger;
use Zend\Log\Writer\Noop as WriterNoop;

class ConfigProvider
{
    /**
     * Return default logger config
     */
    public function __invoke()
    {
        return [
            "dependencies" => $this->getDependencies(),
            "log" => $this->getLog(),
        ];
    }

    /**
     * Return dependencies config
     * @return array
     */
    public function getDependencies()
    {
        return [
            'abstract_factories' => [
                LoggerAbstractServiceFactory::class,
            ],
            'factories' => [
                Logger::class => LoggerServiceFactory::class,
                'LogFilterManager' => FilterPluginManagerFactory::class,
                'LogFormatterManager' => FormatterPluginManagerFactory::class,
                'LogProcessorManager' => ProcessorPluginManagerFactory::class,
                'LogWriterManager' => WriterPluginManagerFactory::class,
            ],
            'aliases' => [],
        ];
    }

    /**
     * Return default config for logger.
     * @return array
     */
    public function getLog()
    {
        return [
            LoggerInterface::class => [
                'writers' => [
                    [
                        'name' => WriterNoop::class,
                    ],
                ],
            ],
        ];
    }
}