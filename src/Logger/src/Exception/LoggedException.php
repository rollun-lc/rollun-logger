<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\logger\Exception;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use rollun\dic\InsideConstruct;
use rollun\logger\Logger;

/**
 * Exception class
 *
 * @category   utils
 * @package    zaboy
 */
class LoggedException extends \Exception implements LoggerAwareInterface
{
    const LOG_LEVEL_DEFAULT = LogLevel::ERROR;

    const LOG_PREVIOUS_LEVEL = LogLevel::INFO;

    /** @var  Logger */
    protected $logger;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var
     */
    protected $trace;

    /**
     * Exception constructor.
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message = "", $code = LogExceptionLevel::ERROR, \Throwable $previous = null)
    {
        InsideConstruct::setConstructParams(['logger' => Logger::DEFAULT_LOGGER_SERVICE]);
        if(!isset($this->logger)) {
            $this->logger = new Logger();
        }
        parent::__construct($message, $code, $previous);
        $exceptionLogging = new ExceptionLogging($this->logger);
        $exceptionLogging->setError($this);
        $this->id = $exceptionLogging->log();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
