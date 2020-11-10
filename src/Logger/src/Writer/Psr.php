<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger\Writer;

use Traversable;
use Psr\Log\LogLevel;
use Psr\Log\LoggerAwareTrait as PsrLoggerAwareTrait;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Psr\Log\NullLogger;
use Zend\Log\Exception\InvalidArgumentException;

/**
 * Proxies log messages to an existing PSR-3 compliant logger.
 */
class Psr extends AbstractWriter
{

    use PsrLoggerAwareTrait;

    /**
     * Default log level (warning)
     *
     * @var int
     */
    protected $defaultLogLevel = LogLevel::WARNING;

    /**
     * Constructor
     *
     * Set options for a writer. Accepted options are:
     *
     * - filters: array of filters to add to this filter
     * - formatter: formatter for this writer
     * - logger: PsrLoggerInterface implementation
     *
     * @param  array|Traversable|PsrLoggerInterface|null $options
     * @throws InvalidArgumentException
     */
    public function __construct($options = null)
    {
        if ($options instanceof PsrLoggerInterface) {
            $this->setLogger($options);
        }

        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        if (is_array($options) && isset($options['logger'])) {
            $this->setLogger($options['logger']);
        }

        parent::__construct($options);

        if (null === $this->logger) {
            $this->setLogger(new NullLogger);
        }
    }

    /**
     * Write a message to the PSR-3 compliant logger.
     *
     * @param array $event event data
     * @return void
     */
    protected function doWrite(array $event)
    {
        $level = $event['level'];
        $message = $event['message'];
        $context = $event['context'];

        $this->logger->log($level, $message, $context);
    }

}
