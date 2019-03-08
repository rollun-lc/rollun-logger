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

class FluentdFormatter implements FormatterInterface
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
        $event = $this->repackEvent($event);
        return json_encode($event);
    }

    /**
     * Clear empty array in and reach up first nested arrays in event
     * @param array $event
     * @return array
     */
    private function repackEvent(array $event)
    {
        $repackEvent = [];
        foreach ($event as $key => $value) {
            if (is_array($value) && count($value) > 0) {
                foreach ($value as $nestedKey => $nestedValue) {
                    $repackEvent["$key.$nestedKey"] = $nestedValue;
                }
            } else if (!is_array($value)) {
                $repackEvent[$key] = $value;
            }
        }
        return $repackEvent;
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