<?php


namespace rollun\test\logger\LoggerTest;


use PHPUnit\Framework\TestCase;
use rollun\logger\Writer\Stream;
use rollun\logger\Writer\WriterInterface;
use RuntimeException;
use rollun\logger\Logger;
use rollun\logger\Writer\Mock;

class FallbackWriterTest extends TestCase
{
    protected const FILENAME = 'stream.log';

    protected function tearDown(): void
    {
        if (file_exists(__DIR__ . '/' . self::FILENAME)) {
            unlink(__DIR__ . '/' . self::FILENAME);
        }
    }

    public function testErrorIsLoggingToFallbackWriter()
    {
        $options = [
            Logger::FALLBACK_WRITER_KEY => [
                'name' => $fallbackWriter = new Mock(),
            ]
        ];
        $logger = new Logger($options);

        $failedWriter = $this->createMock(WriterInterface::class);
        $failedWriter->method('write')
            ->will($this->throwException($exception = new RuntimeException('Fail.')));
        $logger->addWriter($failedWriter);

        $logger->info($message = 'Message');
        $this->assertCount(1, $fallbackWriter->events);

        $event = end($fallbackWriter->events);
        $this->assertContains('failed to write log message.', $event['message']);
        $this->assertEquals($exception, $event['context']['exception']);
        $this->assertEquals($message, $event['context']['failedEvent']['message']);
    }

    /**
     * Assert that error of fallbackWriter writes to logError method.
     * And original message also writes to logError method.
     */
    public function testFallbackWriterFailLogging()
    {
        $failedWriter = $this->createMock(WriterInterface::class);
        $failedWriter->method('write')
            ->will($this->throwException($exception = new RuntimeException('Writer fail.')));

        $failedFallbackWriter = $this->createMock(WriterInterface::class);
        $failedFallbackWriter->method('write')
            ->will($this->throwException($exception = new RuntimeException('Fallback writer fail.')));

        $options = [
            Logger::FALLBACK_WRITER_KEY => [
                'name' => $failedFallbackWriter,
            ]
        ];
        $logger = $this->getMockBuilder(Logger::class)
            ->setConstructorArgs([
                'options' => $options
            ])->setMethods(['logError'])
            ->getMock();
        $logger
            ->expects($this->exactly(2))
            ->method('logError')
            ->with($this->callback(function ($message) {
                $expectedMessages = [
                    'failed to write log message. RuntimeException: Writer fail.',
                    'failed to write log message. RuntimeException: Fallback writer fail.',
                ];

                foreach ($expectedMessages as $expectedMessage) {
                    if ($this->strContains($message, $expectedMessage)) {
                        return true;
                    }
                }
                return false;
            }));

        /** @var Logger $logger */
        $logger->addWriter($failedWriter);
        $logger->info('Message');
    }

    /**
     * Assert that writer error writes to logError methods by default
     * (if no fallback writer is set)
     */
    public function testErrorWithNoFallbackWriter()
    {
        $failedWriter = $this->createMock(WriterInterface::class);
        $failedWriter->method('write')
            ->will($this->throwException($exception = new RuntimeException('Fail.')));

        $logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['logError'])
            ->getMock();
        $logger
            ->method('logError')
            ->willReturnCallback(function (string $error) {
                $this->assertContains('failed to write log message. RuntimeException: Fail.', $error);
            });

        /** @var Logger $logger */
        $logger->addWriter($failedWriter);

        $logger->info('Message');
    }

    /**
     * Assert that configuration (in options) for fallback writer work properly,
     * and Logger correctly resolved by class name
     */
    public function testConfiguration()
    {
        $filename = __DIR__ . '/' . self::FILENAME;
        $options = [
            Logger::FALLBACK_WRITER_KEY => [
                'name' => Stream::class,
                'options' => [
                    'stream' => $filename
                ]
            ]
        ];
        $logger = new Logger($options);

        $failedWriter = $this->createMock(WriterInterface::class);
        $failedWriter->method('write')
            ->will($this->throwException($exception = new RuntimeException('Fail.')));
        $logger->addWriter($failedWriter);

        $logger->info($message = 'Message to be logged');

        $log = file_get_contents($filename);
        $this->assertContains('failed to write log message.', $log);
        $this->assertContains('RuntimeException: Fail.', $log);
        $this->assertContains($message, $log);
    }

    /**
     * Assert method failedWriterEventToString takes the correct value
     */
    public function testFailedWriterEventToString()
    {
        $failedWriter = $this->createMock(WriterInterface::class);
        $failedWriter->method('write')
            ->will($this->throwException($exception = new RuntimeException('Writer fail.')));

        $message = 'Message to be logged';
        $context = ['value' => 'Random context value'];

        $logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['logError', 'failedWriterEventToString'])
            ->getMock();
        $logger
            ->expects($this->once())
            ->method('failedWriterEventToString')
            ->willReturnCallback(function (array $writerEvent) use ($exception, $message, $context, $failedWriter) {
                $this->assertEquals($failedWriter, $writerEvent['writer']);
                $this->assertEquals($exception, $writerEvent['exception']);
                $this->assertEquals($message, $writerEvent['failedEvent']['message']);
                $this->assertEquals($context, $writerEvent['failedEvent']['context']);
                return '';
            });

        /** @var Logger $logger */
        $logger->addWriter($failedWriter);
        $logger->info($message, $context);
    }

    private function strContains(string $haystack, string $needle): bool
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}