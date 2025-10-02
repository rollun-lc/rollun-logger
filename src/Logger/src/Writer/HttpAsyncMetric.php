<?php

declare(strict_types=1);

namespace rollun\logger\Writer;

/**
 * Class HttpAsyncMetric
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class HttpAsyncMetric extends HttpAsync
{
    /**
     * @inheritDoc
     */
    protected function isValid(array $event): bool
    {
        if (!isset($event['context']['metricId']) || !isset($event['context']['value'])) {
            return false;
        }

        // Validate metricId format (3-64 chars, alphanumeric + underscore)
        $metricId = $event['context']['metricId'];
        if (!is_string($metricId) || !preg_match('/^[a-z0-9_]{3,64}$/i', $metricId)) {
            return false;
        }

        // Validate value is numeric
        $value = $event['context']['value'];
        if (!(is_int($value) || is_float($value))) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function parseUrl(array $event): array
    {
        return parse_url("{$this->url}/{$event['context']['metricId']}");
    }
}
