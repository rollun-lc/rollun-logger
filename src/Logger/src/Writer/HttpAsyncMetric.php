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
