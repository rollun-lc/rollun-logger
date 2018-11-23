<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

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

    /** @var LoggerInterface */
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
        $message = sprintf(
            self::LOG_FORMAT,
            empty($response->getStatusCode()) ? $error->getCode() : $response->getStatusCode(),
            empty($request->getMethod()) ? $error->getLine() : $request->getMethod(),
            empty((string)$request->getUri()) ? $error->getFile() : (string)$request->getUri(),
            $error->getMessage()
        );

        try {
            $this->logger->error(
                $message,
                [
                    "status_code" => $response->getStatusCode(),
                    "method" => $request->getMethod(),
                    "uri" => (string)$request->getUri(),
                    "code" => $error->getCode(),
                    "line" => $error->getLine(),
                    "file" => $error->getFile(),
                ]
            );
        } catch (\Throwable $throwable) {
            $logger = new SimpleLogger();
            $logger->alert($throwable->getMessage());// Logger not work, alert situation.
            $logger->error($message);
        }
    }
}
