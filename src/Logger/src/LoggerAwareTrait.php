<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

trait LoggerAwareTrait
{
    /**
     * @var PsrLoggerInterface
     */
    protected $logger = null;

    /**
     * Set logger object
     *
     * @param PsrLoggerInterface $logger
     * @return mixed
     */
    public function setLogger(PsrLoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get logger object
     *
     * @return null|PsrLoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

}
