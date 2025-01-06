<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger\Filter;

use Traversable;
use rollun\logger\Exception\InvalidArgumentException;
use Laminas\Stdlib\ErrorHandler;

class Regex implements FilterInterface
{
    /**
     * Regex to match
     *
     * @var string
     */
    protected $regex;

    /**
     * Filter out any log messages not matching the pattern
     *
     * @param string|array|Traversable $regex Regular expression to test the log message
     * @throws InvalidArgumentException
     */
    public function __construct($regex)
    {
        if ($regex instanceof Traversable) {
            $regex = iterator_to_array($regex);
        }
        if (is_array($regex)) {
            $regex = isset($regex['regex']) ? $regex['regex'] : null;
        }
        ErrorHandler::start(E_WARNING);
        $result = preg_match($regex, '');
        $error  = ErrorHandler::stop();
        if ($result === false) {
            throw new InvalidArgumentException(sprintf(
                'Invalid regular expression "%s"',
                $regex
            ), 0, $error);
        }
        $this->regex = $regex;
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param array $event event data
     * @return bool accepted?
     */
    public function filter(array $event)
    {
        $message = $event['message'];
        if (is_array($event['message'])) {
            $message = var_export($message, true);
        }
        return preg_match($this->regex, $message) > 0;
    }
}
