<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 25.03.17
 * Time: 10:47 AM
 */

namespace rollun\logger\Exception;


use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use rollun\dic\InsideConstruct;
use rollun\logger\Logger;

class ExceptionLogging
{
    const LOG_LEVEL_DEFAULT = LogLevel::ERROR;

    const LOG_PREVIOUS_LEVEL = LogLevel::INFO;

    /** @var \Exception|\Throwable $error */
    protected $error;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * ExceptionParse constructor.
     * @param LoggerInterface $logger
     * @throws LoggedException
     */
    public function __construct(LoggerInterface $logger)
    {
        InsideConstruct::setConstructParams(['logger' => Logger::DEFAULT_LOGGER_SERVICE]);
        if (!isset($this->logger)) {
            throw new LoggedException("Logger not found!", LogLevel::CRITICAL);
        }
    }

    /**
     * @return string
     */
    public function log()
    {
        $level = empty(LogExceptionLevel::getLoggerLevelByCode($this->error->getCode()))
            ? static::LOG_LEVEL_DEFAULT :LogExceptionLevel::getLoggerLevelByCode($this->error->getCode());
        return $this->logException($this->error, $level);
    }

    /**
     * @param  \Exception|\Throwable $error
     * @param null $level
     * @return string
     */
    protected function logException($error, $level = null)
    {
        $prev = $error->getPrevious();
        $prevId = isset($prev) ? $this->previousException($error->getPrevious()) : null;
        $info = $this->exceptionInfo($error);
        if(!is_null($prevId)) {
            $info .= " To get info for previous exception read meessage with id: $prevId";
        }
        $level = $level ?: static::LOG_PREVIOUS_LEVEL;
        return $this->logger->log($level, $info);
    }

    /**
     * @param \Exception|\Throwable $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @param  \Exception|\Throwable $error
     * @return string
     */
    protected function exceptionInfo($error)
    {
        $message = (new \DateTime())->getTimestamp() . " | ";
        $message .= "message: [" . $error->getMessage() . ']';
        $message .= "file: [" . $error->getFile() . ']';
        $message .= "line: [" . $error->getLine() . ']';
        return $message;
    }

    /**
     * @param  \Exception|\Throwable $error
     * @param null $level
     * @return string
     */
    protected function previousException($error, $level = null){
        if (!($error instanceof LoggedException)) {
            return $this->logException($error, $level);
        } else {
            return $error->getId();
        }
    }
}