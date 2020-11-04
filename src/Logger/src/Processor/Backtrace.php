<?php


namespace rollun\logger\Processor;

/**
 * Override ignored namespace property to pass tests.
 *
 * @package rollun\logger\Processor
 */
class Backtrace extends \Zend\Log\Processor\Backtrace
{
    protected $ignoredNamespace = 'rollun\\logger';
}