<?php


namespace rollun\logger\Formatter\Decorator;


use rollun\logger\Filter\FilterInterface;
use rollun\logger\Processor\ProcessorInterface;

class ConditionalProcessingConfig
{
    /** @var string */
    private $name;

    /** @var FilterInterface[] */
    private $filters;

    /** @var ProcessorInterface[] */
    private $processors;

    public function __construct(string $name, array $filters, array $processors)
    {
        $this->name = $name;
        $this->filters = $filters;
        $this->processors = $processors;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getProcessors(): array
    {
        return $this->processors;
    }

}
