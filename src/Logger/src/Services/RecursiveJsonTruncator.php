<?php

namespace rollun\logger\Services;

use InvalidArgumentException;

class RecursiveJsonTruncator implements JsonTruncatorInterface
{
    public function __construct(private RecursiveTruncationParams $params) {}

    /**
     * Returns new copy with changed params
     * @param RecursiveTruncationParams $params
     * @return static
     */
    public function withConfig(RecursiveTruncationParams $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }

    /**
     * Json processing
     */
    public function truncate(string $json): string
    {
        $data = json_decode($json, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException("Invalid JSON");
        }

        // First pass - only lists
        $processed = $this->walk(
            $this->shrinkArrays($data, cutAssociative: false),
            0,
        );
        $result = json_encode($processed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Check size and do second pass if needed
        if (mb_strlen($result) > $this->params->getMaxResultLength()) {
            // Second pass - with associative arrays
            $processed = $this->walk(
                $this->shrinkArrays($data, cutAssociative: true),
                0,
            );
            $result = json_encode($processed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $result;
    }

    private function truncateString($value): string|null
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            $str = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $str = (string) $value;
        }

        if (mb_strlen($str) > $this->params->getMaxLineLength()) {
            return mb_substr($str, 0, $this->params->getMaxLineLength()) . '…';
        }

        return $str;
    }

    /**
     * Проверка, является ли массив списком (индексы 0..N-1)
     */
    private function isList(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    private function shrinkArrays($node, bool $cutAssociative = false)
    {
        if (!is_array($node)) {
            return $node;
        }

        $isList = $this->isList($node);
        $processed = array_map(fn($v) => $this->shrinkArrays($v, $cutAssociative), $node);

        if ($isList) {
            $arrayString = json_encode($processed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (mb_strlen($arrayString) > $this->params->getMaxArrayToStringLength()) {
                $limited = array_slice($processed, 0, $this->params->getMaxArrayElementsAfterCut());
                $limited[] = '…';
                return $limited;
            }
        } elseif ($cutAssociative) {
            $arrayString = json_encode($processed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (mb_strlen($arrayString) > $this->params->getMaxArrayToStringLength()) {
                $keys = array_keys($processed);
                $total = count($keys);
                $keep = $this->params->getMaxArrayElementsAfterCut();

                if ($total > $keep) {
                    $firstHalf = (int) ceil(($keep - 1) / 2);
                    $lastHalf = (int) floor(($keep - 1) / 2);

                    $firstKeys = array_slice($keys, 0, $firstHalf);
                    $lastKeys = array_slice($keys, -$lastHalf);

                    $limited = [];
                    foreach ($firstKeys as $k) {
                        $limited[$k] = $processed[$k];
                    }
                    $limited['…'] = '…';
                    foreach ($lastKeys as $k) {
                        $limited[$k] = $processed[$k];
                    }
                    return $limited;
                }
            }
        }

        return $processed;
    }

    private function walk($node, int $depth)
    {
        if ($depth >= $this->params->getMaxNestingDepth()) {
            return $this->truncateString($node);
        }

        if (!is_array($node)) {
            return $this->truncateString($node);
        }

        return array_map(fn($v) => $this->walk($v, $depth + 1), $node);
    }
}
