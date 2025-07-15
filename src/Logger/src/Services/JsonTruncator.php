<?php

namespace rollun\logger\Services;

use InvalidArgumentException;

/**
 * Truncates json to a given size
 *
 * @package rollun\logger\Services
 */
class JsonTruncator implements JsonTruncatorInterface
{
    /**
     * Minimum value of truncated json size
     *
     * (50 - bytes we need for empty ['truncated' => ...,'truncatedBytes' => ...] json)
     */
    private const MIN_SIZE = 50;

    /**
     * Maximum truncated json size in bytes
     *
     * @var int
     */
    private $maxSize;

    public function __construct(int $maxSize)
    {
        $this->setMaxSize($maxSize);
    }

    public function withMaxSize(int $maxSize): self
    {
        $new = clone $this;
        $new->setMaxSize($maxSize);
        return $new;
    }

    protected function setMaxSize(int $maxSize): void
    {
        $this->validateMaxSize($maxSize);
        $this->maxSize = $maxSize;
    }

    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    private function validateMaxSize(int $maxSize): void
    {
        if ($maxSize < self::MIN_SIZE) {
            throw new InvalidArgumentException('The maximum length must be greater than ' . self::MIN_SIZE);
        }
    }

    public function truncate(string $json): string
    {
        $diff = strlen($json) - $this->maxSize;
        if ($diff <= 0) {
            return $json;
        }

        $json = json_encode([
            // truncated string
            'truncated' => substr($json, 0, $this->maxSize),
            // number of truncated bytes
            'truncatedBytes' => $diff,
        ]);

        return $json;
    }
}
