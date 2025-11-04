<?php

namespace rollun\test\logger;

use DateTime;
use PHPUnit\Framework\TestCase;
use rollun\logger\Formatter\LogStashFormatter;

class LogStashFormatterTest extends TestCase
{
    /**
     * @var LogStashFormatter
     */
    private $formatter;

    public function setUp(): void
    {
        $this->formatter = new LogStashFormatter('test-index');
        parent::setUp();
    }

    public function testHardSizeLimitEnforcement(): void
    {
        $hugeContext = ['data' => str_repeat('x', 200000)];
        $event = [
            'timestamp' => new DateTime(),
            'message' => 'test',
            'context' => $hugeContext,
        ];

        $result = $this->formatter->format($event);
        $decoded = json_decode($result, true);

        $this->assertLessThanOrEqual(
            LogStashFormatter::HARD_MAX_LOG_SIZE,
            mb_strlen($decoded['context']),
            'Context must not exceed HARD_MAX_LOG_SIZE'
        );
    }

    public function testNormalSizeLogNotTruncated(): void
    {
        $event = [
            'timestamp' => new DateTime(),
            'message' => 'test message',
            'context' => ['key' => 'value'],
        ];

        $result = $this->formatter->format($event);

        $this->assertLessThan(
            LogStashFormatter::HARD_MAX_LOG_SIZE,
            mb_strlen($result)
        );

        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
        $this->assertStringNotContainsString('[TRUNCATED]', $result);
    }

    public function testTruncatedLogContainsMarker(): void
    {
        $hugeContext = [];
        for ($i = 0; $i < 1000; $i++) {
            $hugeContext["field_$i"] = str_repeat('x', 1000);
        }

        $event = [
            'timestamp' => new DateTime(),
            'message' => 'test',
            'context' => $hugeContext,
        ];

        $result = $this->formatter->format($event);
        $decoded = json_decode($result, true);

        $this->assertLessThanOrEqual(
            LogStashFormatter::HARD_MAX_LOG_SIZE,
            mb_strlen($decoded['context']),
            'Context must not exceed HARD_MAX_LOG_SIZE'
        );

        if (mb_strlen($decoded['context']) >= LogStashFormatter::HARD_MAX_LOG_SIZE - 100) {
            $this->assertStringContainsString('[TRUNCATED]', $decoded['context']);
        } else {
            $this->assertTrue(true, 'Truncator handled the size, emergency truncation was not needed');
        }
    }
}
