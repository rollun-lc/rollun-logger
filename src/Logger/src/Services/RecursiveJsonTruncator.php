<?php

namespace rollun\logger\Services;

use InvalidArgumentException;

class RecursiveJsonTruncator implements JsonTruncatorInterface
{
    public function __construct(private RecursiveTruncationParamsValueObject $params) {}

    /**
     * Returns new copy with changed params
     * @param RecursiveTruncationParamsValueObject $params
     * @return static
     */
    public function withConfig(RecursiveTruncationParamsValueObject $params): self
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

        $processed = $this->walk(
            $this->shrinkArrays($data),
            0,
        );

        return json_encode($processed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function truncateString($value): string|null
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            $str = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $str = (string)$value;
        }

        if (mb_strlen($str) > $this->params->getLimit()) {
            return mb_substr($str, 0, $this->params->getLimit()) . '…';
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

    private function shrinkArrays($node)
    {
        if (!is_array($node)) {
            return $node;
        }

        $isList = $this->isList($node);
        $processed = array_map(fn($v) => $this->shrinkArrays($v), $node);

        if ($isList) {
            $arrayString = json_encode($processed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (mb_strlen($arrayString) > $this->params->getMaxArrayChars()) {
                $limited = array_slice($processed, 0, $this->params->getArrayLimit());
                $limited[] = '…';
                return $limited;
            }
        }

        return $processed;
    }

    private function walk($node, int $depth)
    {
        if ($depth >= $this->params->getDepthLimit()) {
            return $this->truncateString($node);
        }

        if (!is_array($node)) {
            return $this->truncateString($node);
        }

        return array_map(fn($v) => $this->walk($v, $depth + 1), $node);
    }
}
