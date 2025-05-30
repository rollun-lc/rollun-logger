<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger\Writer;

use rollun\logger\Exception\InvalidArgumentException;
use rollun\logger\Formatter\FormatterInterface;
use Traversable;
use rollun\logger\Filter\FilterInterface;
use rollun\logger\Filter\Priority as PriorityFilter;
use rollun\logger\WriterPluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;

/**
 * Buffers all events until the strategy determines to flush them.
 *
 * @see        http://packages.python.org/Logbook/api/handlers.html#logbook.FingersCrossedHandler
 */
class FingersCrossed extends AbstractWriter
{
    /**
     * Writer plugins
     *
     * @var WriterPluginManager
     */
    protected $writerPlugins;

    /**
     * Flag if buffering is enabled
     *
     * @var bool
     */
    protected $buffering = true;

    /**
     * Oldest entries are removed from the buffer if bufferSize is reached.
     * 0 is infinte buffer size.
     *
     * @var int
     */
    protected $bufferSize;

    /**
     * array of log events
     *
     * @var array
     */
    protected $buffer = [];

    /**
     * Constructor
     *
     * @param WriterInterface|string|array|Traversable $writer Wrapped writer or array of configuration options
     * @param FilterInterface|int|null $filterOrPriority Filter or log priority which determines buffering of events
     * @param int $bufferSize Maximum buffer size
     */
    public function __construct(
        protected $writer,
        $filterOrPriority = null,
        $bufferSize = 0
    ) {
        if ($this->writer instanceof Traversable) {
            $this->writer = ArrayUtils::iteratorToArray($this->writer);
        }

        if (is_array($this->writer)) {
            $filterOrPriority = $this->writer['priority'] ?? null;
            $bufferSize       = $this->writer['bufferSize'] ?? null;
            $this->writer           = $this->writer['writer'] ?? null;
        }

        if (null === $filterOrPriority) {
            // TODO: Change priority to constant. 4 - warn level
            $filterOrPriority = new PriorityFilter(4);
        } elseif (! $filterOrPriority instanceof FilterInterface) {
            $filterOrPriority = new PriorityFilter($filterOrPriority);
        }

        if (is_array($this->writer) && isset($this->writer['name'])) {
            $this->setWriter($this->writer['name'], $this->writer['options']);
        } else {
            $this->setWriter($this->writer);
        }
        $this->addFilter($filterOrPriority);
        $this->bufferSize = $bufferSize;
    }

    /**
     * Set a new writer
     *
     * @param  string|WriterInterface $writer
     * @param  array|null $options
     * @return self
     * @throws InvalidArgumentException
     */
    public function setWriter($writer, array $options = null)
    {
        if (is_string($writer)) {
            $writer = $this->writerPlugin($writer, $options);
        }

        if (! $writer instanceof WriterInterface) {
            throw new InvalidArgumentException(sprintf(
                'Writer must implement %s\WriterInterface; received "%s"',
                __NAMESPACE__,
                get_debug_type($writer)
            ));
        }

        $this->writer = $writer;
        return $this;
    }

    /**
     * Get writer plugin manager
     *
     * @return WriterPluginManager
     */
    public function getWriterPluginManager()
    {
        if (null === $this->writerPlugins) {
            $this->setWriterPluginManager(new WriterPluginManager(new ServiceManager()));
        }
        return $this->writerPlugins;
    }

    /**
     * Set writer plugin manager
     *
     * @param  string|WriterPluginManager $plugins
     * @return FingersCrossed
     * @throws InvalidArgumentException
     */
    public function setWriterPluginManager($plugins)
    {
        if (is_string($plugins)) {
            $plugins = new $plugins();
        }
        if (! $plugins instanceof WriterPluginManager) {
            throw new InvalidArgumentException(sprintf(
                'Writer plugin manager must extend %s\WriterPluginManager; received %s',
                __NAMESPACE__,
                get_debug_type($plugins)
            ));
        }

        $this->writerPlugins = $plugins;
        return $this;
    }

    /**
     * Get writer instance
     *
     * @param string $name
     * @param array|null $options
     * @return WriterInterface
     */
    public function writerPlugin(string $name, array $options = null)
    {
        return $this->getWriterPluginManager()->get($name, $options);
    }

    /**
     * Log a message to this writer.
     *
     * @param array $event log data event
     * @return void
     */
    public function write(array $event): void
    {
        $this->doWrite($event);
    }

    /**
     * Check if buffered data should be flushed
     *
     * @param array $event event data
     * @return bool true if buffered data should be flushed
     */
    protected function isActivated(array $event)
    {
        foreach ($this->filters as $filter) {
            if (! $filter->filter($event)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Write message to buffer or delegate event data to the wrapped writer
     *
     * @param array $event event data
     * @return void
     */
    protected function doWrite(array $event)
    {
        if (! $this->buffering) {
            $this->writer->write($event);
            return;
        }

        $this->buffer[] = $event;

        if ($this->bufferSize > 0 && count($this->buffer) > $this->bufferSize) {
            array_shift($this->buffer);
        }

        if (! $this->isActivated($event)) {
            return;
        }

        $this->buffering = false;

        foreach ($this->buffer as $bufferedEvent) {
            $this->writer->write($bufferedEvent);
        }
    }

    /**
     * Resets the state of the handler.
     * Stops forwarding records to the wrapped writer
     */
    public function reset()
    {
        $this->buffering = true;
    }

    /**
     * Stub in accordance to parent method signature.
     * Formatters must be set on the wrapped writer.
     *
     * @param string|FormatterInterface $formatter
     * @param array|null $options
     * @return WriterInterface
     */
    public function setFormatter($formatter, array $options = null)
    {
        return $this->writer;
    }

    /**
     * Record shutdown
     *
     * @return void
     */
    public function shutdown()
    {
        $this->writer->shutdown();
        $this->buffer = null;
    }
}
