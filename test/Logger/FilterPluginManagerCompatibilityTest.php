<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\test\logger;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use rollun\logger\Filter\FilterInterface;
use rollun\logger\Filter\Priority;
use rollun\logger\Filter\Regex;
use rollun\logger\FilterPluginManager;
use Zend\Log\Exception\InvalidArgumentException;
use Zend\Log\Filter;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Test\CommonPluginManagerTrait;

class FilterPluginManagerCompatibilityTest extends TestCase
{
    use CommonPluginManagerTrait;
    use ServicesNotSharedByDefaultTrait;

    protected function getPluginManager()
    {
        return new FilterPluginManager(new ServiceManager());
    }

    protected function getV2InvalidPluginException()
    {
        return InvalidArgumentException::class;
    }

    protected function getInstanceOf()
    {
        return FilterInterface::class;
    }

    /**
     * Overrides CommonPluginManagerTrait::aliasProvider
     *
     * Iterates through aliases, and for adapters that require extensions,
     * tests if the extension is loaded, skipping that alias if not.
     *
     * @return \Traversable
     * @throws \ReflectionException
     */
    public function aliasProvider()
    {
        $pluginManager = $this->getPluginManager();
        $r = new ReflectionProperty($pluginManager, 'aliases');
        $r->setAccessible(true);
        $aliases = $r->getValue($pluginManager);

        foreach ($aliases as $alias => $target) {
            switch ($target) {
                case Priority::class:
                    // intentionally fall through
                case Regex::class:
                    // intentionally fall through
                case Filter\Timestamp::class:
                    // intentionally fall through
                case Filter\Validator::class:
                    // Skip, as these each have required arguments
                    break;
                default:
                    yield $alias => [$alias, $target];
            }
        }
    }
}
