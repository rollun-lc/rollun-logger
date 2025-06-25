<?php

namespace rollun\logger\Services;

use InvalidArgumentException;

class AdvancedJsonTruncator implements JsonTruncatorInterface
{
    public function __construct(private array $params = [])
    {
        $this->setParams($params);
    }

    /**
     * Returns new copy with changed params
     * @param array $params
     * @return static
     */
    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->setParams($params);
        return $clone;
    }

    /**
     * Set params
     */
    protected function setParams(array $params): void
    {
        foreach ($params as $key => $value) {
            if (!array_key_exists($key, $this->params)) {
                throw new InvalidArgumentException("Unknown param: $key");
            }
            $this->params[$key] = $value;
        }
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
            0
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
        if (mb_strlen($str) > $this->params['limit']) {
            return mb_substr($str, 0, $this->params['limit']) . '…';
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
            if (mb_strlen($arrayString) > $this->params['maxArrayChars']) {
                $limited = array_slice($processed, 0, $this->params['arrayLimit']);
                $limited[] = '…';
                return $limited;
            }
        }
        return $processed;
    }

    private function walk($node, int $depth)
    {
        if ($depth >= $this->params['depthLimit']) {
            return $this->truncateString($node);
        }
        if (!is_array($node)) {
            return $this->truncateString($node);
        }
        return array_map(fn($v) => $this->walk($v, $depth + 1), $node);
    }
}