<?php


namespace Logger\Middleware;


use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use rollun\logger\Middleware\RequestLoggedMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

class RequestLoggerMiddlewareTest extends TestCase
{
    /**
     * Assert that middleware write to log in correct format with correct data
     */
    public function testLogMessage()
    {
        $request = (new ServerRequest(['HTTP_CLIENT_IP' => '172.20.0.1']))
            ->withMethod('POST')
            ->withUri(new Uri('http://localhost/test'));

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->willReturnCallback(function (string $message) {
                $messageParts = explode(' ', $message);
                // Expected format '[Datetime] Method - URL <- Ip address' and there is 6 parts separated by space
                $this->assertCount(6, $messageParts);

                // remove first and last character ('[' and ']')
                $dateTime = substr($messageParts[0], 1, -1);
                $this->assertTrue($this->validISO8601Date($dateTime));

                $this->assertEquals('POST', $messageParts[1]);
                $this->assertEquals('-', $messageParts[2]);
                $this->assertEquals('/test', $messageParts[3]);
                $this->assertEquals('<-', $messageParts[4]);
                $this->assertEquals('172.20.0.1', $messageParts[5]);
            });

        /** @var LoggerInterface $logger */
        $middleware = new RequestLoggedMiddleware($logger);

        /** @var ServerRequestInterface $request */
        /** @var RequestHandlerInterface $requestHandler */
        $middleware->process($request, $requestHandler);
    }

    /**
     * ISO 8601 Date Validation
     * @see https://stackoverflow.com/questions/8003446/php-validate-iso-8601-date-string
     *
     * @param $value
     * @return bool
     * @throws Exception
     */
    function validISO8601Date($value)
    {
        if (!is_string($value)) {
            return false;
        }

        $dateTime = new DateTime($value);

        if ($dateTime) {
            $tmp = $dateTime->format('c');
            return $tmp === $value;
        }

        return false;
    }
}