<?php


namespace rollun\logger\Processor;


use rollun\token\LifeCycleToken;
use Zend\Log\Processor\ProcessorInterface;

class LifeCycleTokenInjector implements ProcessorInterface
{

    /**
     * @var LifeCycleToken
     */
    protected $token;

    /**
     * TokenInjector constructor.
     * @param LifeCycleToken $token
     */
    public function __construct(LifeCycleToken $token)
    {
        $this->token = $token;
    }

    /**
     * Processes a log message before it is given to the writers
     *
     * @param  array $event
     * @return array
     */
    public function process(array $event)
    {
        if(!isset($event["content"][LifeCycleToken::class])) {
            $event["content"][LifeCycleToken::class] = $this->token->toString();
        }
        return $event;
    }
}