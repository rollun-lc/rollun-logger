<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger\Formatter;

use rollun\logger\Exception\InvalidArgumentException;
use Traversable;

class Simple extends Base
{

    const DEFAULT_FORMAT = '%timestamp% %level% (%priority%): %message% %context%';

    /**
     * Format specifier for log messages
     *
     * @var string
     */
    protected $format;

    /**
     * Class constructor
     *
     * @see http://php.net/manual/en/function.date.php
     * @param null|string $format Format specifier for log messages
     * @param null|string $dateTimeFormat Format specifier for DateTime objects in event data
     * @throws InvalidArgumentException
     */
    public function __construct($format = null, $dateTimeFormat = null)
    {
        if ($format instanceof Traversable) {
            $format = iterator_to_array($format);
        }

        if (is_array($format)) {
            $dateTimeFormat = isset($format['dateTimeFormat']) ? $format['dateTimeFormat'] : null;
            $format = isset($format['format']) ? $format['format'] : null;
        }

        if (isset($format) && !is_string($format)) {
            throw new InvalidArgumentException('Format must be a string');
        }

        $this->format = isset($format) ? $format : static::DEFAULT_FORMAT;

        parent::__construct($dateTimeFormat);
    }

    /**
     * Formats data into a single line to be written by the writer.
     *
     * @param array $event event data
     * @return string formatted line to write to the log
     */
    public function format($event)
    {
        $output = $this->format;

        $event = parent::format($event);
        foreach ($event as $name => $value) {
            if ('context' == $name && count($value)) {
                $value = $this->normalize($value);
            } elseif ('context' == $name) {
                // Don't print an empty array
                $value = '';
            }
            $output = str_replace("%$name%", $value, $output);
        }

        if (isset($event['context']) && empty($event['context']) && false !== strpos($this->format, '%context%')
        ) {
            $output = rtrim($output, ' ');
        }
        return $output;
    }

}
