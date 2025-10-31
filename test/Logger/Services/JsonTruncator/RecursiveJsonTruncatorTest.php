<?php

namespace rollun\test\logger\Services\JsonTruncator;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use rollun\logger\Services\RecursiveJsonTruncator;
use rollun\logger\Services\JsonTruncatorInterface;
use rollun\logger\Services\RecursiveTruncationParams;

class RecursiveJsonTruncatorTest extends TestCase
{
    /**
     * @var string
     */
    private static $inputJson;

    /**
     * @var JsonTruncatorInterface
     */
    private $jsonTruncator;

    public static function setUpBeforeClass()
    {
        self::$inputJson = file_get_contents(__DIR__ . '/data/input_test_data.json');
    }

    public function setUp()
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
                    'maxResultLength' => 1000000,
                ],
                __DIR__ . '/data/truncated_result_1.json'
            ],
            'different_limits_2' => [
                [
                    'maxLineLength' => 50,
                    'maxNestingDepth' => 2,
                    'maxArrayToStringLength' => 200,
                    'maxArrayElementsAfterCut' => 2,
                    'maxResultLength' => 1000000,
                ],
                __DIR__ . '/data/truncated_result_2.json'
            ],
            'different_limits_3' => [
                [
                    'maxLineLength' => 30,
                    'maxNestingDepth' => 1,
                    'maxArrayToStringLength' => 100,
                    'maxArrayElementsAfterCut' => 1,
                    'maxResultLength' => 1000000,
                ],
                __DIR__ . '/data/truncated_result_3.json'
            ],
            'different_limits_4' => [
                [
                    'maxLineLength' => 200,
                    'maxNestingDepth' => 5,
                    'maxArrayToStringLength' => 5000,
                    'maxArrayElementsAfterCut' => 10,
                    'maxResultLength' => 1000000,
                ],
                __DIR__ . '/data/truncated_result_4.json'
            ],
            'different_limits_5' => [
                [
                    'maxLineLength' => 10,
                    'maxNestingDepth' => 2,
                    'maxArrayToStringLength' => 50,
                    'maxArrayElementsAfterCut' => 1,
                    'maxResultLength' => 1000000,
                ],
                __DIR__ . '/data/truncated_result_5.json'
            ],
        ];
    }

    /**
     * @dataProvider jsonManifestTruncationProvider
     */
    public function testTruncateHugeManifestJson(array $params, $expectedPath)
    {
        $expected = json_decode(file_get_contents($expectedPath), true);
        $actual = json_decode(
            $this->jsonTruncator
                ->withConfig(
                    RecursiveTruncationParams::createFromArray($params)
                )
                ->truncate(self::$inputJson),
            true
        );

        $this->assertEquals(
            $expected,
            $actual,
            "Result for inputted Json does not match $expectedPath"
        );
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
    public function testInvalidJsonThrowsException($invalidJson)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->jsonTruncator->truncate($invalidJson);
    }

    public function testWithWrongParams()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->jsonTruncator
            ->withConfig(
                RecursiveTruncationParams::createFromArray(['maxLineLength' => -1])
            )
            ->truncate(self::$inputJson);
    }

    public static function defaultScenariosProvider(): array
    {
        return [
            'just_string' => [
                'AAAAAAAAAAAAAA',
                [
                    'maxLineLength' => 4,
                ],
                'AAAA…'
            ],
            'array_cut' => [
                [1,2,3,4,5,6,7,8,9],
                [
                    'maxLineLength' => 4,
                    'maxArrayToStringLength' => 5,
                    'maxArrayElementsAfterCut' => 2,
                ],
                [1,2,'…']
            ],
            'array_no_cut' => [
                [1,2,3],
                [
                    'maxLineLength' => 4,
                    'maxArrayToStringLength' => 10,
                    'maxArrayElementsAfterCut' => 2,
                ],
                [1,2,3]
            ],
            'array_one_element' => [
                ['element' => ['one']],
                [
                    'maxLineLength' => 4,
                    'maxNestingDepth' => 2,
                    'maxArrayToStringLength' => 20,
                    'maxArrayElementsAfterCut' => 2,
                ],
                ['element' => ['one']]
            ],
            'empty_input' => [
                [],
                [
                    'maxLineLength' => 4,
                    'maxNestingDepth' => 2,
                    'maxArrayToStringLength' => 20,
                    'maxArrayElementsAfterCut' => 2,
                ],
                []
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
            ]
        ];
    }

    /**
     * @dataProvider defaultScenariosProvider
     */
    public function testWithDefaultScenarios($input, $params, $expected)
    {
        $actual = json_decode(
            $this->jsonTruncator
                ->withConfig(
                    RecursiveTruncationParams::createFromArray($params)
                )
                ->truncate(json_encode($input)),
            true
        );
        $this->assertEquals($expected, $actual);
    }

    public function testTruncateKeepsValidJson()
    {
        $numbers = range(1, 50);
        $jsonInput = json_encode(['nums' => $numbers]);
        $truncator = $this->jsonTruncator->withConfig(
            RecursiveTruncationParams::createFromArray([
                'maxLineLength' => 100,
                'maxNestingDepth' => 1,
                'maxArrayToStringLength' => 20,
                'maxArrayElementsAfterCut' => 5,
            ])
        );

        $result = $truncator->truncate($jsonInput);

        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded, 'Decoded result should be an array');
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Truncated output must be valid JSON');
    }

    public function testMaxResultLengthEnforcement(): void
    {
        $largeData = ['data' => range(1, 10000)];
        $params = RecursiveTruncationParams::createFromArray([
            'maxResultLength' => 1000,
        ]);

        $result = $this->jsonTruncator->withConfig($params)->truncate(json_encode($largeData));

        $this->assertLessThanOrEqual(1000, mb_strlen($result));
    }

    public function testMaxResultLengthValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        RecursiveTruncationParams::createFromArray(['maxResultLength' => 0]);
    }

    public function testAssociativeArrayMiddleCut(): void
    {
        $data = [
            'assoc' => [
                'first' => 1,
                'second' => 2,
                'third' => 3,
                'fourth' => 4,
                'fifth' => 5,
                'sixth' => 6,
            ],
        ];

        $params = RecursiveTruncationParams::createFromArray([
            'maxResultLength' => 50,
            'maxArrayToStringLength' => 30,
            'maxArrayElementsAfterCut' => 4,
        ]);

        $result = json_decode(
            $this->jsonTruncator->withConfig($params)->truncate(json_encode($data)),
            true
        );

        $this->assertArrayHasKey('first', $result['assoc']);
        $this->assertArrayHasKey('sixth', $result['assoc']);
        $this->assertArrayHasKey('…', $result['assoc']);
    }

    public function testTwoPassTruncation(): void
    {
        $data = [
            'list' => range(1, 100),
            'assoc' => array_combine(
                array_map(fn($i) => "key_$i", range(1, 100)),
                range(1, 100)
            ),
        ];

        $params = RecursiveTruncationParams::createFromArray([
            'maxResultLength' => 500,
            'maxArrayToStringLength' => 200,
            'maxArrayElementsAfterCut' => 3,
        ]);

        $result = json_decode(
            $this->jsonTruncator->withConfig($params)->truncate(json_encode($data)),
            true
        );

        $this->assertEquals(4, count($result['list']));
        $this->assertArrayHasKey('…', $result['assoc']);
    }
}
