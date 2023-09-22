<?php


namespace rollun\logger\Formatter\Decorator;


use rollun\logger\Formatter\FormatterInterface;

class ConditionalProcessing extends AbstractDecorator
{
    /** @var ConditionalProcessingConfig[] */
    private $config;

    public function __construct(FormatterInterface $formatter, array $config)
    {
        parent::__construct($formatter);
        $this->config = $config;
    }

    public function format(array $event)
    {
        foreach ($this->config as $configItem) {
            foreach ($configItem->getFilters() as $filter) {
                if (!$filter->filter($event)) {
                    continue 2;
                }
            }

            foreach ($configItem->getProcessors() as $processor) {
                $event = $processor->process($event);
            }
        }

        return parent::format($event);
    }
}
