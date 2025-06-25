<?php

namespace Rollun\Test\Logger\Services\JsonTruncator;

use PHPUnit\Framework\TestCase;
use rollun\logger\Services\AdvancedJsonTruncator;
use rollun\logger\Services\JsonTruncatorInterface;

class AdvancedJsonTruncatorTest extends TestCase
{
    private static string $inputJson;

    private JsonTruncatorInterface $jsonTruncator;

    public static function setUpBeforeClass(): void
    {
        static::$inputJson = file_get_contents(__DIR__ . '/data/input_test_data.json');
    }

    public function setUp(): void
    {
        $this->jsonTruncator = new AdvancedJsonTruncator([
            'limit' => 1000,
            'depthLimit' => 3,
            'maxArrayChars' => 1000,
            'arrayLimit' => 3,
        ]);
    }

    /**
     * @dataProvider jsonTruncationProvider
     */
    public function testTruncateHugeManifestJson(array $params, string $expectedPath)
    {
        $expected = json_decode(file_get_contents($expectedPath), true);
        $actual = json_decode(
            $this->jsonTruncator->withParams($params)->truncate(static::$inputJson),
            true
        );
        $this->assertEquals($expected, $actual, "Result for inputted Json does not match $expectedPath");
    }

    public static function jsonTruncationProvider(): array
    {
        return [
            'different_limits_1' => [
                [
                    'limit' => 100,
                    'depthLimit' => 3,
                    'maxArrayChars' => 1000,
                    'arrayLimit' => 3,
                ],
                __DIR__ . '/data/truncated_result_1.json',
            ],
            'different_limits_2' => [
                [
                    'limit' => 50,
                    'depthLimit' => 2,
                    'maxArrayChars' => 200,
                    'arrayLimit' => 2,
                ],
                __DIR__ . '/data/truncated_result_2.json',
            ],
            'different_limits_3' => [
                [
                    'limit' => 30,
                    'depthLimit' => 1,
                    'maxArrayChars' => 100,
                    'arrayLimit' => 1,
                ],
                __DIR__ . '/data/truncated_result_3.json',
            ],
            'different_limits_4' => [
                [
                    'limit' => 200,
                    'depthLimit' => 5,
                    'maxArrayChars' => 5000,
                    'arrayLimit' => 10,
                ],
                __DIR__ . '/data/truncated_result_4.json',
            ],
            'different_limits_5' => [
                [
                    'limit' => 10,
                    'depthLimit' => 2,
                    'maxArrayChars' => 50,
                    'arrayLimit' => 1,
                ],
                __DIR__ . '/data/truncated_result_5.json',
            ],
        ];
    }
}