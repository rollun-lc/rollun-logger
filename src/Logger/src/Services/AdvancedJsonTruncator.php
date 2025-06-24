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
     * Возвращает новый экземпляр с изменёнными параметрами
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
     * Устанавливает параметры (с валидаторами, если нужно)
     */
    protected function setParams(array $params): void
    {
        foreach ($params as $key => $value) {
            if (!array_key_exists($key, $this->params)) {
                throw new InvalidArgumentException("Unknown param: $key");
            }
            // можно добавить тут проверки диапазонов/типов
            $this->params[$key] = $value;
        }
    }

    /**
     * Обработка json с учётом параметров, аналогично твоему JSONata
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
//        return json_encode($processed);
    }


    protected function truncateString($value)
    {
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

    protected function shrinkArrays($node)
    {
        if (is_array($node) && array_keys($node) === range(0, count($node) - 1)) { // is indexed
            $processed = array_map([$this, 'shrinkArrays'], $node);

            // check string length of array
            $arrayString = json_encode($processed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (mb_strlen($arrayString) > $this->params['maxArrayChars']) {
                $limited = array_slice($processed, 0, $this->params['arrayLimit']);
                $limited[] = '…';
                return $limited;
            }
            return $processed;
        } elseif (is_array($node)) { // assoc
            $res = [];
            foreach ($node as $k => $v) {
                $res[$k] = $this->shrinkArrays($v);
            }
            return $res;
        }
        return $node;
    }

    protected function walk($node, $depth)
    {
        if ($depth >= $this->params['depthLimit']) {
            return $this->truncateString($node);
        }

        if (is_array($node) && array_keys($node) === range(0, count($node) - 1)) { // is indexed
            return array_map(function ($v) use ($depth) {
                return $this->walk($v, $depth + 1);
            }, $node);
        } elseif (is_array($node)) { // assoc
            $res = [];
            foreach ($node as $k => $v) {
                $res[$k] = $this->walk($v, $depth + 1);
            }
            return $res;
        } else {
            return $this->truncateString($node);
        }
    }
}