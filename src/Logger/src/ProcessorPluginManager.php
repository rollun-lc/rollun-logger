<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger;

use rollun\logger\Processor\Backtrace;
use rollun\logger\Processor\ProcessorInterface;
use rollun\logger\Processor\PsrPlaceholder;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\Factory\InvokableFactory;

/**
 * Plugin manager for log processors.
 */
class ProcessorPluginManager extends AbstractPluginManager
{
    protected $aliases = [
        'backtrace'      => Backtrace::class,
        'psrplaceholder' => PsrPlaceholder::class,
    ];

    protected $factories = [
        Backtrace::class      => InvokableFactory::class,
        PsrPlaceholder::class => InvokableFactory::class,
        // Legacy (v2) due to alias resolution; canonical form of resolved
        // alias is used to look up the factory, while the non-normalized
        // resolved alias is used as the requested name passed to the factory.
        'zendlogprocessorbacktrace'      => InvokableFactory::class,
        'zendlogprocessorpsrplaceholder' => InvokableFactory::class,
    ];

    protected $instanceOf = ProcessorInterface::class;

    /**
     * Allow many processors of the same type (v2)
     * @param bool
     */
    protected $shareByDefault = false;

    /**
     * Allow many processors of the same type (v3)
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
                get_class($this),
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
        } catch (InvalidServiceException $e) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Plugin of type %s is invalid; must implement %s\Processor\ProcessorInterface',
                (get_debug_type($plugin)),
                __NAMESPACE__
            ));
        }
    }
}
