<?php


namespace rollun\logger\Formatter\Decorator;


use rollun\logger\Formatter\FormatterInterface;

class AbstractDecorator implements FormatterInterface
{
    /** @var FormatterInterface */
    protected $formatter;

    public function __construct(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    public function format(array $event)
    {
        return $this->formatter->format($event);
    }

    public function getDateTimeFormat(): string
    {
        return $this->formatter->getDateTimeFormat();
    }

    public function setDateTimeFormat(string $dateTimeFormat): FormatterInterface
    {
        return $this->formatter->setDateTimeFormat($dateTimeFormat);
    }
}
