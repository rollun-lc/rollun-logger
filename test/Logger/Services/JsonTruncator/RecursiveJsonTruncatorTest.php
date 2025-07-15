<?php

namespace Rollun\Test\Logger\Services\JsonTruncator;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use rollun\logger\Services\RecursiveJsonTruncator;
use rollun\logger\Services\JsonTruncatorInterface;
use rollun\logger\Services\RecursiveTruncationParams;

class RecursiveJsonTruncatorTest extends TestCase
{
    private static string $inputJson;

    private JsonTruncatorInterface $jsonTruncator;

    public static function setUpBeforeClass(): void
    {
        static::$inputJson = file_get_contents(__DIR__ . '/data/input_test_data.json');
    }

    public function setUp(): void
    {
        $params = RecursiveTruncationParams::createFromArray([
            'maxLineLength' => 1000,
            'maxNestingDepth' => 3,
            'maxArrayToStringLength' => 1000,
            'maxArrayElementsAfterCut' => 3,
        ]);
        $this->jsonTruncator = new RecursiveJsonTruncator($params);
    }

    public static function jsonManifestTruncationProvider(): array
    {
        return [
            'different_limits_1' => [
                [
                    'maxLineLength' => 100,
                    'maxNestingDepth' => 3,
                    'maxArrayToStringLength' => 1000,
                    'maxArrayElementsAfterCut' => 3,
                ],
                __DIR__ . '/data/truncated_result_1.json',
            ],
            'different_limits_2' => [
                [
                    'maxLineLength' => 50,
                    'maxNestingDepth' => 2,
                    'maxArrayToStringLength' => 200,
                    'maxArrayElementsAfterCut' => 2,
                ],
                __DIR__ . '/data/truncated_result_2.json',
            ],
            'different_limits_3' => [
                [
                    'maxLineLength' => 30,
                    'maxNestingDepth' => 1,
                    'maxArrayToStringLength' => 100,
                    'maxArrayElementsAfterCut' => 1,
                ],
                __DIR__ . '/data/truncated_result_3.json',
            ],
            'different_limits_4' => [
                [
                    'maxLineLength' => 200,
                    'maxNestingDepth' => 5,
                    'maxArrayToStringLength' => 5000,
                    'maxArrayElementsAfterCut' => 10,
                ],
                __DIR__ . '/data/truncated_result_4.json',
            ],
            'different_limits_5' => [
                [
                    'maxLineLength' => 10,
                    'maxNestingDepth' => 2,
                    'maxArrayToStringLength' => 50,
                    'maxArrayElementsAfterCut' => 1,
                ],
                __DIR__ . '/data/truncated_result_5.json',
            ],
        ];
    }

    /**
     * @dataProvider jsonManifestTruncationProvider
     */
    public function testTruncateHugeManifestJson(array $params, string $expectedPath)
    {
        $expected = json_decode(file_get_contents($expectedPath), true);
        $actual = json_decode(
            $this->jsonTruncator->withConfig(RecursiveTruncationParams::createFromArray($params))->truncate(
                static::$inputJson,
            ),
            true,
        );
        $this->assertEquals($expected, $actual, "Result for inputted Json does not match $expectedPath");
    }

    public static function invalidJsonProvider(): array
    {
        return [
            'malformed_braces' => ['{invalid: json}'],
            'unterminated_array' => ['[1,2,3'],
            'just_text' => ['just plain text'],
            'empty_string' => [''],
        ];
    }

    /**
     * @dataProvider invalidJsonProvider
     */
    public function testInvalidJsonThrowsException(string $invalidJson): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->jsonTruncator->truncate($invalidJson);
    }

    public function testWithWrongParams(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->jsonTruncator->withConfig(
            RecursiveTruncationParams::createFromArray(['maxLineLength' => -1]),
        )->truncate(static::$inputJson);
    }

    public static function defaultScenariosProvider(): array
    {
        return [
            'just_string' => [
                'AAAAAAAAAAAAAA',
                [
                    'maxLineLength' => 4,
                ],
                'AAAA…',
            ],
            'array_cut' => [
                [
                    1,
                    2,
                    3,
                    4,
                    5,
                    6,
                    7,
                    8,
                    9,
                ],
                [
                    'maxLineLength' => 4,
                    'maxArrayToStringLength' => 5,
                    'maxArrayElementsAfterCut' => 2,
                ],
                [
                    1,
                    2,
                    '…',
                ],
            ],
            'array_no_cut' => [
                [
                    1,
                    2,
                    3,
                ],
                [
                    'maxLineLength' => 4,
                    'maxArrayToStringLength' => 10,
                    'maxArrayElementsAfterCut' => 2,
                ],
                [
                    1,
                    2,
                    3,
                ],
            ],
            'array_one_element' => [
                [
                    "element" => ['one'],
                ],
                [
                    'maxLineLength' => 4,
                    'maxNestingDepth' => 2,
                    'maxArrayToStringLength' => 20,
                    'maxArrayElementsAfterCut' => 2,
                ],
                [
                    "element" => ['one'],
                ],
            ],
            'empty_input' => [
                [],
                [
                    'maxLineLength' => 4,
                    'maxNestingDepth' => 2,
                    'maxArrayToStringLength' => 20,
                    'maxArrayElementsAfterCut' => 2,
                ],
                [],
            ],
            'array_depth_cut' => [
                ['root' => ['a' => ['b' => ['c' => 'deep']]]],
                [
                    'maxLineLength' => 10,
                    'maxNestingDepth' => 2,
                    'maxArrayToStringLength' => 100,
                    'maxArrayElementsAfterCut' => 5,
                ],
                [
                    'root' => [
                        'a' => '{"b":{"c":…',
                    ],
                ],
            ],
            'deep_nesting_3_levels' => [
                [
                    'lvl0' => [
                        'lvl1' => [
                            'lvl2' => [
                                'lvl3' => [
                                    'lvl4' => [
                                        'lvl5' => [
                                            'lvl6' => 'value',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'maxLineLength' => 1000,
                    'maxNestingDepth' => 3,
                    'maxArrayToStringLength' => 1000,
                    'maxArrayElementsAfterCut' => 5,
                ],
                [
                    'lvl0' => [
                        'lvl1' => [
                            'lvl2' =>
                                '{"lvl3":{"lvl4":{"lvl5":{"lvl6":"value"}}}}',
                        ],
                    ],
                ],
            ],
            'integer_input' => [
                1,
                [
                    'maxLineLength' => 100,
                ],
                1,
            ],
            'bool_input' => [
                true,
                [
                    'maxLineLength' => 100,
                ],
                true,
            ],
        ];
    }

    /**
     * @dataProvider defaultScenariosProvider
     */
    public function testWithDefaultScenarios($input, $params, $expected): void
    {
        $actual = json_decode(
            $this->jsonTruncator->withConfig(RecursiveTruncationParams::createFromArray($params))->truncate(
                json_encode($input),
            ),
            true,
        );
        $this->assertEquals($expected, $actual);
    }

    public function testTruncateKeepsValidJson(): void
    {
        $numbers = range(1, 50);
        $jsonInput = json_encode(['nums' => $numbers]);
        $truncator = $this->jsonTruncator->withConfig(RecursiveTruncationParams::createFromArray([
            'maxLineLength' => 100,
            'maxNestingDepth' => 1,
            'maxArrayToStringLength' => 20,
            'maxArrayElementsAfterCut' => 5,
        ]));

        $result = $truncator->truncate($jsonInput);

        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded, 'Decoded result should be an array');
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Truncated output must be valid JSON');
    }
}
