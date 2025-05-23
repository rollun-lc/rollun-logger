<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger\Processor;

interface ProcessorInterface
{
    /**
     * Processes a log message before it is given to the writers
     *
     * @param  array $event
     * @return array
     */
    public function process(array $event): array;
}
