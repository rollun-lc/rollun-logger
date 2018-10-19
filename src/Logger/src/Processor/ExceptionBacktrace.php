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
     * Begin from the last cached exception
     * Put result in $event['context']['backtrace']
     *
     * Return:
     *  [
     *      // The last one exception
     *      [
     *          'line' => result of 'getLine' method,
     *          'file' => result of 'getFile' method,
     *          'code' => result of 'getCode' method,
     *          'message' => result of 'getMessage' method,
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

        return $event;
    }

    /**
     * Process exception and all previous exceptions to return one-level array with exceptions backtrace
     *
     * @param \Throwable $e
     * @param int $stack
     * @return array
     */
    public function getExceptionBacktrace(\Throwable $e, $stack = 0)
    {
        $backtrace[$stack] = [
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
        ];

        if ($e->getPrevious()) {
            return array_merge(
                $backtrace,
                $this->getExceptionBacktrace($e->getPrevious(), ++$stack)
            );
        }

        return $backtrace;
    }
}
