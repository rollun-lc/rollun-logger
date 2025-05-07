<?php


namespace rollun\logger\Processor;


use rollun\logger\Filter\FilterInterface;

class ConditionalProcessor implements ProcessorInterface
{
    /**
     * @param FilterInterface[] $filters
     * @param ProcessorInterface[] $processors
     */
    public function __construct(private array $filters, private array $processors)
    {
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
