<?php

namespace rollun\logger\Services;

use InvalidArgumentException;

final class RecursiveTruncationParamsValueObject
{
    private const DEFAULT_LIMIT = 1000;
    private const DEFAULT_DEPTH_LIMIT = 3;
    private const DEFAULT_MAX_ARRAY_CHARS = 1000;
    private const DEFAULT_ARRAY_LIMIT = 3;

    public function __construct(
        private int $limit = self::DEFAULT_LIMIT,
        private int $depthLimit = self::DEFAULT_DEPTH_LIMIT,
        private int $maxArrayChars = self::DEFAULT_MAX_ARRAY_CHARS,
        private int $arrayLimit = self::DEFAULT_ARRAY_LIMIT,
    ) {
        if ($limit < 1) {
            throw new InvalidArgumentException('limit (max line length) must be >= 1');
        }
        if ($depthLimit < 0) {
            throw new InvalidArgumentException('depthLimit (maximum nesting depth) must be >= 0');
        }
        if ($maxArrayChars < 1) {
            throw new InvalidArgumentException('maxArrayChars (max (string)array threshold) must be >= 1');
        }
        if ($arrayLimit < 1) {
            throw new InvalidArgumentException(
                'arrayLimit (if > maxArrayChars, we leave only the first N elements) must be >= 1',
            );
        }
    }

    /**
     * Creates ValueObject from array.
     */
    public static function createFromArray(array $a): self
    {
        return new self(
            $a['limit'] ?? self::DEFAULT_LIMIT,
            $a['depthLimit'] ?? self::DEFAULT_DEPTH_LIMIT,
            $a['maxArrayChars'] ?? self::DEFAULT_MAX_ARRAY_CHARS,
            $a['arrayLimit'] ?? self::DEFAULT_ARRAY_LIMIT,
        );
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getDepthLimit(): int
    {
        return $this->depthLimit;
    }

    public function getMaxArrayChars(): int
    {
        return $this->maxArrayChars;
    }

    public function getArrayLimit(): int
    {
        return $this->arrayLimit;
    }

    public function withLimit(int $limit): self
    {
        return new self(
            $limit,
            $this->depthLimit,
            $this->maxArrayChars,
            $this->arrayLimit,
        );
    }

    public function withDepthLimit(int $depthLimit): self
    {
        return new self(
            $this->limit,
            $depthLimit,
            $this->maxArrayChars,
            $this->arrayLimit,
        );
    }

    public function withMaxArrayChars(int $maxArrayChars): self
    {
        return new self(
            $this->limit,
            $this->depthLimit,
            $maxArrayChars,
            $this->arrayLimit,
        );
    }

    public function withArrayLimit(int $arrayLimit): self
    {
        return new self(
            $this->limit,
            $this->depthLimit,
            $this->maxArrayChars,
            $arrayLimit,
        );
    }
}
