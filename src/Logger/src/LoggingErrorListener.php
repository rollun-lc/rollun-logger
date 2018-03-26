<?php


namespace rollun\logger;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class LoggingErrorListener
{
    /**
     * Log format for messages:
     *
     * STATUS [METHOD] path: message
     */
    const LOG_FORMAT = '%d [%s] %s: %s';

    /** @var LoggerInterface  */
    private $logger;

    /**
     * LoggingErrorListener constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param $error
     * @param $request
     * @param $response
     */
    public function __invoke(Throwable $error, ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->logger->error(sprintf(
            self::LOG_FORMAT,
            $response->getStatusCode(),
            $request->getMethod(),
            (string) $request->getUri(),
            $error->getMessage()
        ));
    }
}