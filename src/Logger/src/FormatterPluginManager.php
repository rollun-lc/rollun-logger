<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger;

use rollun\logger\Formatter\Base;
use rollun\logger\Formatter\Db;
use rollun\logger\Formatter\FormatterInterface;
use rollun\logger\Formatter\Simple;
use rollun\logger\Exception\InvalidArgumentException;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\Factory\InvokableFactory;

class FormatterPluginManager extends AbstractPluginManager
{
    protected $aliases = [
        'base'             => Base::class,
        'simple'           => Simple::class,
        'db'               => Db::class,
    ];

    protected $factories = [
        Base::class                       => InvokableFactory::class,
        Formatter\Simple::class           => InvokableFactory::class,
        Formatter\Db::class               => InvokableFactory::class,
        // Legacy (v2) due to alias resolution; canonical form of resolved
        // alias is used to look up the factory, while the non-normalized
        // resolved alias is used as the requested name passed to the factory.
        'zendlogformatterbase'             => InvokableFactory::class,
        'zendlogformattersimple'           => InvokableFactory::class,
        'zendlogformatterdb'               => InvokableFactory::class,
    ];

    protected $instanceOf = FormatterInterface::class;

    /**
     * Allow many formatters of the same type (v2)
     * @param bool
     */
    protected $shareByDefault = false;

    /**
     * Allow many formatters of the same type (v3)
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
     * @throws InvalidArgumentException
     */
    public function validatePlugin($plugin)
    {
        try {
            $this->validate($plugin);
        } catch (InvalidServiceException) {
            throw new InvalidArgumentException(sprintf(
                'Plugin of type %s is invalid; must implement %s\Formatter\FormatterInterface',
                (get_debug_type($plugin)),
                __NAMESPACE__
            ));
        }
    }
}
