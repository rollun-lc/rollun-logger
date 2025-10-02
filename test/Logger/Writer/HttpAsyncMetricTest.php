<?php

declare(strict_types=1);

namespace Rollun\Test\Logger\Writer;

use PHPUnit\Framework\TestCase;
use rollun\logger\Writer\HttpAsyncMetric;

/**
 * Class HttpAsyncMetricTest
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class HttpAsyncMetricTest extends TestCase
{
    private const TEST_URL = 'http://some-url.com/metrics';
    private const TEST_HOST = 'some-url.com';
    private const VALID_METRIC_ID = 'test_metric_123';
    private const INVALID_METRIC_ID_SHORT = 'ab';
    private const INVALID_METRIC_ID_LONG = 'a' . 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    private const INVALID_METRIC_ID_SPECIAL = 'test-metric@123';

    /**
     * @return iterable
     */
    public function getValidEventProvider(): iterable
    {
        yield 'integer value' => [
            [
                'context' => [
                    'metricId' => self::VALID_METRIC_ID,
                    'value' => 42,
                ],
            ],
        ];

        yield 'float value' => [
            [
                'context' => [
                    'metricId' => 'cpu_usage',
                    'value' => 85.5,
                ],
            ],
        ];

        yield 'with additional context' => [
            [
                'context' => [
                    'metricId' => 'memory_usage',
                    'value' => 1024,
                    'unit' => 'MB',
                    'timestamp' => time(),
                ],
            ],
        ];
    }

    /**
     * @return iterable
     */
    public function getInvalidEventProvider(): iterable
    {
        yield 'missing metricId' => [
            [
                'context' => [
                    'value' => 42,
                ],
            ],
        ];

        yield 'missing value' => [
            [
                'context' => [
                    'metricId' => self::VALID_METRIC_ID,
                ],
            ],
        ];

        yield 'missing context' => [
            [
                'message' => 'test message',
            ],
        ];

        yield 'metricId too short' => [
            [
                'context' => [
                    'metricId' => self::INVALID_METRIC_ID_SHORT,
                    'value' => 42,
                ],
            ],
        ];

        yield 'metricId too long' => [
            [
                'context' => [
                    'metricId' => self::INVALID_METRIC_ID_LONG,
                    'value' => 42,
                ],
            ],
        ];

        yield 'metricId with special chars' => [
            [
                'context' => [
                    'metricId' => self::INVALID_METRIC_ID_SPECIAL,
                    'value' => 42,
                ],
            ],
        ];

        yield 'non-numeric value' => [
            [
                'context' => [
                    'metricId' => self::VALID_METRIC_ID,
                    'value' => 'not a number',
                ],
            ],
        ];

        yield 'null value' => [
            [
                'context' => [
                    'metricId' => self::VALID_METRIC_ID,
                    'value' => null,
                ],
            ],
        ];
    }

    /**
     * @dataProvider getValidEventProvider
     */
    public function testIsValidReturnsTrueForValidEvents(array $event)
    {
        $writer = $this->createWriter(['url' => self::TEST_URL]);
        
        $this->assertTrue($writer->isValid($event));
    }

    /**
     * @dataProvider getInvalidEventProvider
     */
    public function testIsValidReturnsFalseForInvalidEvents(array $event)
    {
        $writer = $this->createWriter(['url' => self::TEST_URL]);
        
        $this->assertFalse($writer->isValid($event));
    }

    public function testParseUrlAddsMetricIdToUrl()
    {
        $writer = $this->createWriter(['url' => self::TEST_URL]);
        
        $event = [
            'context' => [
                'metricId' => self::VALID_METRIC_ID,
                'value' => 42,
            ],
        ];

        $parsedUrl = $writer->parseUrl($event);
        
        $this->assertEquals(self::TEST_HOST, $parsedUrl['host']);
        $this->assertEquals('/metrics/' . self::VALID_METRIC_ID, $parsedUrl['path']);
    }

    public function testWriteSkipsInvalidEvents()
    {
        $writer = new class (['url' => self::TEST_URL]) extends HttpAsyncMetric {
            public $sendCalled = false;
            
            protected function send(string $host, int $port, string $out)
            {
                $this->sendCalled = true;
            }
        };

        $invalidEvent = [
            'context' => [
                'metricId' => self::INVALID_METRIC_ID_SHORT,
                'value' => 42,
            ],
        ];

        $writer->write($invalidEvent);

        $this->assertFalse($writer->sendCalled, 'send() must not be called for invalid events');
    }

    public function testWriteProcessesValidEvents()
    {
        $writer = new class (['url' => self::TEST_URL]) extends HttpAsyncMetric {
            public $sendCalled = false;
            public $lastEvent = null;
            
            protected function send(string $host, int $port, string $out)
            {
                $this->sendCalled = true;
            }
            
            protected function doWrite(array $event)
            {
                $this->lastEvent = $event;
                parent::doWrite($event);
            }
        };

        $validEvent = [
            'context' => [
                'metricId' => self::VALID_METRIC_ID,
                'value' => 42,
            ],
        ];

        $writer->write($validEvent);

        $this->assertTrue($writer->sendCalled, 'send() must be called for valid events');
        $this->assertEquals($validEvent, $writer->lastEvent);
    }

    /**
     * @return HttpAsyncMetric
     */
    private function createWriter(array $options): HttpAsyncMetric
    {
        return new class ($options) extends HttpAsyncMetric {
            public function isValid(array $event): bool
            {
                return parent::isValid($event);
            }
            
            public function parseUrl(array $event): array
            {
                return parent::parseUrl($event);
            }
        };
    }
}
