<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\logger;

use Psr\Log\LoggerInterface;
use rollun\logger\Processor\ExceptionBacktrace;
use rollun\logger\Processor\IdMaker;
use Zend\Log\LoggerAbstractServiceFactory;
use Zend\Log\LoggerServiceFactory;
use Zend\Log\FilterPluginManagerFactory;
use Zend\Log\FormatterPluginManagerFactory;
use Zend\Log\ProcessorPluginManagerFactory;
use Zend\Log\Writer\Stream;
use Zend\Log\WriterPluginManagerFactory;
use Zend\Log\Logger;

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
                        'name' => Stream::class,
                        'options' => [
                            'stream' => 'php://stdout'
                        ]
                    ],
                ],
                'processors' => [
                    [
                        'name' => IdMaker::class,
                    ],
                    [
                        'name' => ExceptionBacktrace::class
                    ]
                ],
            ],
        ];
    }
}
