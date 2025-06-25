<?php

namespace Rollun\Test\Logger\Services\JsonTruncator;

use InvalidArgumentException;
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
     * @dataProvider jsonManifestTruncationProvider
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

    public static function jsonManifestTruncationProvider(): array
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

    /**
     * @dataProvider invalidJsonProvider
     */
    public function testInvalidJsonThrowsException(string $invalidJson): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->jsonTruncator->truncate($invalidJson);
    }

    public static function invalidJsonProvider(): array
    {
        return [
            'malformed_braces'    => ['{invalid: json}'],
            'unterminated_array'  => ['[1,2,3'],
            'just_text'           => ['just plain text'],
            'empty_string'        => [''],
        ];
    }

    public function testWithWrongParams(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->jsonTruncator->withParams(['unknown' => 1])->truncate(static::$inputJson);
    }

    /**
     * @dataProvider defaultScenariosProvider
     */
    public function testWithDefaultScenarios($input, $params, $expected): void
    {
        $actual = json_decode($this->jsonTruncator->withParams($params)->truncate(json_encode($input)), true);
        $this->assertEquals($expected, $actual);
    }

    public static function defaultScenariosProvider(): array
    {
        return [
            'just_string' => [
                [
                    'text' => 'AAAAAAAAAAAAAA'
                ],
                [
                    'limit' => 4
                ],
                [
                    'text' => 'AAAA…'
                ]
            ],
            'array_cut' => [
                [
                    1, 2, 3, 4, 5, 6, 7, 8, 9
                ],
                [
                    'limit' => 4,
                    'maxArrayChars' => 5,
                    'arrayLimit' => 2,
                ],
                [
                    1, 2, '…'
                ]
            ],
            'array_no_cut' => [
                [
                    1, 2, 3
                ],
                [
                    'limit' => 4,
                    'maxArrayChars' => 10,
                    'arrayLimit' => 2,
                ],
                [
                    1, 2, 3
                ]
            ],
            'array_one_element' => [
                [
                    "element" => ['one']
                ],
                [
                    'limit' => 4,
                    'depthLimit' => 2,
                    'maxArrayChars' => 20,
                    'arrayLimit' => 2,
                ],
                [
                    "element" => ['one']
                ]
            ],
            'empty_input' => [
                [],
                [
                    'limit' => 4,
                    'depthLimit' => 2,
                    'maxArrayChars' => 20,
                    'arrayLimit' => 2,
                ],
                []
            ],
            'array_depth_cut' => [
                ['root' => ['a' => ['b' => ['c' => 'deep']]]],
                [
                    'limit'         => 10,
                    'depthLimit'    => 2,
                    'maxArrayChars' => 100,
                    'arrayLimit'    => 5,
                ],
                [
                    'root' => [
                        'a' => '{"b":{"c":…'
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
                    'limit'         => 1000,
                    'depthLimit'    => 3,
                    'maxArrayChars' => 1000,
                    'arrayLimit'    => 5,
                ],
                [
                    'lvl0' => [
                        'lvl1' => [
                            'lvl2' =>
                            // json_encode(['lvl3'=>['lvl4'=>['lvl5'=>['lvl6'=>'value']]]])
                                '{"lvl3":{"lvl4":{"lvl5":{"lvl6":"value"}}}}',
                        ],
                    ],
                ],
            ],
            'integer_input' => [
                1,
                [
                    'limit' => 100,
                ],
                1
            ],
            'bool_input' => [
                true,
                [
                    'limit' => 100,
                ],
                true
            ],
        ];
    }

    public function testTruncateKeepsValidJson(): void
    {
        // 4.1: Результат остаётся валидным JSON
        $numbers   = range(1, 50);
        $jsonInput = json_encode(['nums' => $numbers]);
        $truncator = $this->jsonTruncator->withParams([
            'limit'         => 100,
            'depthLimit'    => 1,
            'maxArrayChars' => 20,
            'arrayLimit'    => 5,
        ]);

        $result = $truncator->truncate($jsonInput);

        // Попытка декодировать – должна вернуть массив, а не false
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded, 'Decoded result should be an array');
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Truncated output must be valid JSON');
    }

}