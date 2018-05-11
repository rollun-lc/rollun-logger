<?php


namespace rollun\logger\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class RequestLoggedMiddleware implements MiddlewareInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * RequestLoggedMiddleware constructor.
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $message = ""
            . "[" . (new \DateTime())->format("c") . "] "
            . $request->getMethod() . " - "
            . $request->getUri()->getPath()
            . (!empty($request->getUri()->getQuery()) ? ("?" . $request->getUri()->getQuery() . " ") : " ")
            . "<- " . $this->resolveSenderIp($request);

        $this->logger->info($message);
        $response = $delegate->process($request);
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