<?php

namespace rollun\logger\Processor;

use InvalidArgumentException;
use Zend\Log\Processor\ProcessorInterface;

/**
 * Class ExceptionBacktrace
 * @package rollun\logger\Processor
 */
class ExceptionBacktrace implements ProcessorInterface
{
    /**
     * Get backtrace from exception (with all previous exceptions)
     * Begin from the last caught exception
     * Put result in $event['context']['backtrace']
     *
     * Return:
     *  [
     *      // The last one exception
     *      [
     *          'line' => result of 'getLine' \Throwable method,
     *          'file' => result of 'getFile' \Throwable method,
     *          'code' => result of 'getCode' \Throwable method,
     *          'message' => result of 'getMessage' \Throwable method,
     *      ],
     *
     *      // The next previous exception and so on
     *      [
     *          //...
     *      ],
     *  ]
     *
     * @param array $event
     * @return array
     */
    public function process(array $event)
    {
        if (!isset($event['context']['exception'])) {
            return $event;
        }

        $exception = $event['context']['exception'];

        if (!($exception instanceof \Throwable)) {
            throw new InvalidArgumentException(
                'Exception argument must implement \Throwable interface, ' . get_class($exception) . ' given'
            );
        }

        $backtrace = $this->getExceptionBacktrace($exception);
        $event['context']['backtrace'] = $backtrace;

        // Remove exception from context to prevent serialize it to log result message
        unset($event['context']['exception']);

        return $event;
    }

    /**
     * Process exception and all previous exceptions to return one-level array with exceptions backtrace
     *
     * @param \Throwable $e
     * @return array
     */
    public function getExceptionBacktrace(\Throwable $e)
    {
        $backtrace[] = [
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
        ];

        if ($e->getPrevious()) {
            return array_merge(
                $backtrace,
                $this->getExceptionBacktrace($e->getPrevious())
            );
        }

        return $backtrace;
    }
}
