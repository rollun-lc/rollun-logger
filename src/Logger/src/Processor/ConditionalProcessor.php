<?php


namespace rollun\logger\Processor;


use rollun\logger\Filter\FilterInterface;

class ConditionalProcessor implements ProcessorInterface
{
    /** @var FilterInterface[] */
    private $filters;

    /** @var ProcessorInterface[] */
    private $processors;

    public function __construct(array $filters, array $processors)
    {
        $this->filters = $filters;
        $this->processors = $processors;
    }

    public function process(array $event): array
    {
        foreach ($this->filters as $filter) {
            if (!$filter->filter($event)) {
                return $event;
            }
        }

        foreach ($this->processors as $processor) {
            $event = $processor->process($event);
        }

        return $event;
    }
}
