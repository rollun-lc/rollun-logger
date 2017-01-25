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
    /** @var  string */
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
     * @param LoggerInterface $logger
     */
    public function __construct($message = "", $code = LogExceptionLevel::ERROR, \Exception $previous = null)
    {
        InsideConstruct::setConstructParams(['logger' => 'logger']);
        parent::__construct($message, $code, $previous);
        $prevId = isset($previous) ? $this->previousException($previous) : null;
        $message = isset($prevId) ? (new \DateTime())->getTimestamp() . " | " . $this->message .
            " To get info for previous exception read meessage with id" :
            (new \DateTime())->getTimestamp() . "|" . $this->message;
        $level = empty(LogExceptionLevel::getLoggerLevelByCode($code))
            ? static::LOG_LEVEL_DEFAULT :LogExceptionLevel::getLoggerLevelByCode($code);
        $this->id = $this->logger->log($level, $message);

    }

    /**
     * @param \Exception $exception
     * @return string
     */
    public function previousException(\Exception $exception)
    {
        if (!($exception instanceof LoggedException)) {
            $prev = $exception->getPrevious();
            $prevId = isset($prev) ? $this->previousException($exception->getPrevious()) : null;
            $message = !empty($prevId) ? $this->message .
                " To get info for previous exception read meessage with id: " . $prevId : $this->message;
            return $this->logger->log(static::LOG_PREVIOUS_LEVEL, $message);
        } else {
            return $exception->getId();
        }
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
