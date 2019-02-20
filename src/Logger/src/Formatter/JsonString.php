<?php
/**
 * Created by PhpStorm.
 * User: itprofessor02
 * Date: 20.02.19
 * Time: 17:46
 */

namespace rollun\logger\Formatter;


use RuntimeException;
use Zend\Log\Formatter\FormatterInterface;

class JsonString implements FormatterInterface
{

    /**
     * Formats data into a single line to be written by the writer.
     *
     * @param array $event event data
     * @return string|array Either a formatted line to write to the log, or the
     *     updated event information to provide to the writer.
     */
    public function format($event)
    {
        return json_encode($event);
    }

    /**
     * Get the format specifier for DateTime objects
     *
     * @return string
     */
    public function getDateTimeFormat()
    {
        return 'c';
    }

    /**
     * Set the format specifier for DateTime objects
     *
     * @see http://php.net/manual/en/function.date.php
     * @param string $dateTimeFormat DateTime format
     * @return FormatterInterface
     */
    public function setDateTimeFormat($dateTimeFormat)
    {
        ///its crutch
        throw new RuntimeException("Setup dateTime format not supported...");
    }
}