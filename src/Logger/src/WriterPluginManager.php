<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger;

use rollun\logger\Writer\Db;
use rollun\logger\Writer\Factory\DbFactory;
use rollun\logger\Writer\Factory\WriterFactory;
use rollun\logger\Writer\FingersCrossed;
use rollun\logger\Writer\Mock;
use rollun\logger\Writer\Noop;
use rollun\logger\Writer\Psr;
use rollun\logger\Writer\Stream;
use rollun\logger\Exception\InvalidArgumentException;
use rollun\logger\Writer\WriterInterface;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;

/**
 * Plugin manager for log writers.
 */
class WriterPluginManager extends AbstractPluginManager
{
    protected $aliases = [
        'db'             => Db::class,
        'fingerscrossed' => FingersCrossed::class,
        'mock'           => Mock::class,
        'noop'           => Noop::class,
        'psr'            => Psr::class,
        'stream'         => Stream::class,
    ];

    protected $factories = [
        Db::class                    => DbFactory::class,
        Mock::class                  => WriterFactory::class,
        Noop::class                  => WriterFactory::class,
        Psr::class                   => WriterFactory::class,
        Stream::class                => WriterFactory::class,
        // Legacy (v2) due to alias resolution; canonical form of resolved
        // alias is used to look up the factory, while the non-normalized
        // resolved alias is used as the requested name passed to the factory.
        'zendlogwriterdb'             => WriterFactory::class,
        'zendlogwritermock'           => WriterFactory::class,
        'zendlogwriternoop'           => WriterFactory::class,
        'zendlogwriterpsr'            => WriterFactory::class,
        'zendlogwriterstream'         => WriterFactory::class,
    ];

    protected $instanceOf = WriterInterface::class;

    /**
     * Allow many writers of the same type (v2)
     * @param bool
     */
    protected $shareByDefault = false;

    /**
     * Allow many writers of the same type (v3)
     * @param bool
     */
    protected $sharedByDefault = false;

    /**
     * Validate the plugin is of the expected type (v3).
     *
     * Validates against `$instanceOf`.
     *
     * @param mixed $instance
     * @throws InvalidServiceException
     */
    public function validate($instance)
    {
        if (! $instance instanceof $this->instanceOf) {
            throw new InvalidServiceException(sprintf(
                '%s can only create instances of %s; %s is invalid',
                static::class,
                $this->instanceOf,
                (get_debug_type($instance))
            ));
        }
    }

    /**
     * Validate the plugin is of the expected type (v2).
     *
     * Proxies to `validate()`.
     *
     * @param mixed $plugin
     * @throws InvalidServiceException
     */
    public function validatePlugin($plugin)
    {
        try {
            $this->validate($plugin);
        } catch (InvalidServiceException) {
            throw new InvalidArgumentException(sprintf(
                'Plugin of type %s is invalid; must implement %s\Writer\WriterInterface',
                (get_debug_type($plugin)),
                __NAMESPACE__
            ));
        }
    }
}
