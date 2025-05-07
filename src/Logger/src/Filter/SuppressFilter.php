<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger\Filter;

use rollun\logger\Exception\InvalidArgumentException;
use Traversable;


class SuppressFilter implements FilterInterface
{
    /**
     * @var bool
     */
    protected $accept = true;

    /**
     * This is a simple boolean filter.
     *
     * @param int|array|Traversable|false $suppress
     * @throws InvalidArgumentException
     */
    public function __construct($suppress = false)
    {
        if ($suppress instanceof Traversable) {
            $suppress = iterator_to_array($suppress);
        }
        if (is_array($suppress)) {
            $suppress = $suppress['suppress'] ?? false;
        }
        if (! is_bool($suppress)) {
            throw new InvalidArgumentException(
                sprintf('Suppress must be a boolean; received "%s"', gettype($suppress))
            );
        }

        $this->suppress($suppress);
    }

    /**
     * This is a simple boolean filter.
     *
     * Call suppress(true) to suppress all log events.
     * Call suppress(false) to accept all log events.
     *
     * @param  bool $suppress Should all log events be suppressed?
     * @return void
     */
    public function suppress($suppress)
    {
        $this->accept = ! (bool) $suppress;
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param array $event event data
     * @return bool accepted?
     */
    public function filter(array $event)
    {
        return $this->accept;
    }
}
