<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\logger\Middleware;

use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class RequestLoggedMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * Logging all incoming requests in format '[Datetime] Method - URL <- Ip address'
     * example: '[2020-11-12T10:15:02+00:00] GET - /api/webhook/cron?param=true <- 172.20.0.1'
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $message = "[" . (new DateTime())->format("c") . "] ";
        $message .= $request->getMethod() . " - " . $request->getUri()->getPath();
        $message .= (!empty($request->getUri()->getQuery()) ? ("?" . $request->getUri()->getQuery() . " ") : " ");
        $message .= "<- " . $this->resolveSenderIp($request);

        $this->logger->info($message);
        $response = $handler->handle($request);

        return $response;
    }

    /**
     * @param $request
     * @return string
     */
    private function resolveSenderIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        if (!empty($serverParams["HTTP_CLIENT_IP"])) {
            $senderIp = $serverParams["HTTP_CLIENT_IP"];
        } elseif (!empty($serverParams["HTTP_X_FORWARDED_FOR"])) {
            $senderIp = $serverParams["HTTP_X_FORWARDED_FOR"];
        } else {
            $senderIp = $serverParams["REMOTE_ADDR"];
        }

        return $senderIp;
    }
}
