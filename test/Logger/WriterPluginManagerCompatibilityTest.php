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
use ReflectionException;
use ReflectionProperty;
use rollun\logger\Writer\Db;
use rollun\logger\Writer\FingersCrossed;
use rollun\logger\Writer\Stream;
use rollun\logger\Exception\InvalidArgumentException;
use rollun\logger\Writer\WriterInterface;
use rollun\logger\WriterPluginManager;
use Traversable;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;

class WriterPluginManagerCompatibilityTest extends TestCase
{
    use CommonPluginManagerTrait;
    use ServicesNotSharedByDefaultTrait;

    protected static function getPluginManager(): WriterPluginManager
    {
        return new WriterPluginManager(new ServiceManager());
    }

    protected function getV2InvalidPluginException()
    {
        return InvalidArgumentException::class;
    }

    protected function getInstanceOf()
    {
        return WriterInterface::class;
    }

    /**
     * Overrides CommonPluginManagerTrait::aliasProvider
     *
     * Iterates through aliases, and for adapters that require extensions,
     * tests if the extension is loaded, skipping that alias if not.
     *
     * @return Traversable
     * @throws ReflectionException
     */
    public function aliasProvider()
    {
        $pluginManager = $this->getPluginManager();
        $r = new ReflectionProperty($pluginManager, 'aliases');
        $r->setAccessible(true);
        $aliases = $r->getValue($pluginManager);

        foreach ($aliases as $alias => $target) {
            switch ($target) {
                case Db::class:
                    // intentionally fall-through
                case FingersCrossed::class:
                    // intentionally fall-through
                case Stream::class:
                    // always skip; these implementations have required arguments
                    break;
                default:
                    yield $alias => [$alias, $target];
            }
        }
    }
}
