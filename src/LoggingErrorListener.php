<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 25.03.17
 * Time: 10:31 AM
 */

namespace rollun\logger;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use rollun\dic\InsideConstruct;
use rollun\logger\Exception\ExceptionLogging;
use rollun\logger\Exception\LoggedException;

class LoggingErrorListener
{
    /** @var  LoggerInterface */
    protected $logger;

    /** @var ExceptionLogging  */
    protected $exceptionLogging;

    public function __construct(LoggerInterface $logger)
    {
        InsideConstruct::setConstructParams(['logger' => Logger::DEFAULT_LOGGER_SERVICE]);
        if (!isset($this->logger)) {
            throw new LoggedException("Logger not found!", LogLevel::CRITICAL);
        }
        $this->exceptionLogging = new ExceptionLogging($this->logger);
    }

    public function __invoke($error, ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->exceptionLogging->setError($error);
        $this->exceptionLogging->log();
    }
}