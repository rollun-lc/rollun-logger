<?php

namespace rollun\logger\Services;

use InvalidArgumentException;

final class RecursiveTruncationParams
{
    private const DEFAULT_MAX_LINE_LENGTH = 1000;
    private const DEFAULT_MAX_NESTING_DEPTH = 3;
    private const DEFAULT_MAX_ARRAY_TO_STRING_LENGTH = 1000;
    private const DEFAULT_MAX_ARRAY_ELEMENTS_AFTER_CUT = 3;
    private const DEFAULT_MAX_RESULT_LENGTH = 102400;

    public function __construct(
        private int $maxLineLength              = self::DEFAULT_MAX_LINE_LENGTH,
        private int $maxNestingDepth            = self::DEFAULT_MAX_NESTING_DEPTH,
        private int $maxArrayToStringLength     = self::DEFAULT_MAX_ARRAY_TO_STRING_LENGTH,
        private int $maxArrayElementsAfterCut   = self::DEFAULT_MAX_ARRAY_ELEMENTS_AFTER_CUT,
        private int $maxResultLength            = self::DEFAULT_MAX_RESULT_LENGTH,
    ) {
        $this->validate();
    }

    /**
     * Creates ValueObject from array.
     */
    public static function createFromArray(array $a): self
    {
        return new self(
            $a['maxLineLength'] ?? self::DEFAULT_MAX_LINE_LENGTH,
            $a['maxNestingDepth'] ?? self::DEFAULT_MAX_NESTING_DEPTH,
            $a['maxArrayToStringLength'] ?? self::DEFAULT_MAX_ARRAY_TO_STRING_LENGTH,
            $a['maxArrayElementsAfterCut'] ?? self::DEFAULT_MAX_ARRAY_ELEMENTS_AFTER_CUT,
            $a['maxResultLength'] ?? self::DEFAULT_MAX_RESULT_LENGTH,
        );
    }

    private function validate()
    {
        if ($this->maxLineLength < 1) {
            throw new InvalidArgumentException('limit (max line length) must be >= 1');
        }
        if ($this->maxNestingDepth < 0) {
            throw new InvalidArgumentException('depthLimit (maximum nesting depth) must be >= 0');
        }
        if ($this->maxArrayToStringLength < 1) {
            throw new InvalidArgumentException('maxArrayChars (max (string)array threshold) must be >= 1');
        }
        if ($this->maxArrayElementsAfterCut < 1) {
            throw new InvalidArgumentException(
                'arrayLimit (if > maxArrayChars, we leave only the first N elements) must be >= 1',
            );
        }
        if ($this->maxResultLength < 1) {
            throw new InvalidArgumentException('maxResultLength must be >= 1');
        }
    }

    public function getMaxLineLength(): int
    {
        return $this->maxLineLength;
    }

    public function getMaxNestingDepth(): int
    {
        return $this->maxNestingDepth;
    }

    public function getMaxArrayToStringLength(): int
    {
        return $this->maxArrayToStringLength;
    }

    public function getMaxArrayElementsAfterCut(): int
    {
        return $this->maxArrayElementsAfterCut;
    }

    public function getMaxResultLength(): int
    {
        return $this->maxResultLength;
    }

    public function withMaxLineLength(int $maxLineLength): self
    {
        return new self(
            $maxLineLength,
            $this->maxNestingDepth,
            $this->maxArrayToStringLength,
            $this->maxArrayElementsAfterCut,
            $this->maxResultLength,
        );
    }

    public function withMaxNestingDepth(int $maxNestingDepth): self
    {
        return new self(
            $this->maxLineLength,
            $maxNestingDepth,
            $this->maxArrayToStringLength,
            $this->maxArrayElementsAfterCut,
            $this->maxResultLength,
        );
    }

    public function withMaxArrayToStringLength(int $maxArrayToStringLength): self
    {
        return new self(
            $this->maxLineLength,
            $this->maxNestingDepth,
            $maxArrayToStringLength,
            $this->maxArrayElementsAfterCut,
            $this->maxResultLength,
        );
    }

    public function withMaxArrayElementsAfterCut(int $maxArrayElementsAfterCut): self
    {
        return new self(
            $this->maxLineLength,
            $this->maxNestingDepth,
            $this->maxArrayToStringLength,
            $maxArrayElementsAfterCut,
            $this->maxResultLength,
        );
    }

    public function withMaxResultLength(int $maxResultLength): self
    {
        return new self(
            $this->maxLineLength,
            $this->maxNestingDepth,
            $this->maxArrayToStringLength,
            $this->maxArrayElementsAfterCut,
            $maxResultLength,
        );
    }
}
