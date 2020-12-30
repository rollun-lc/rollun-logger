<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\logger\Processor;

use rollun\logger\LifeCycleToken;

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
    public function process(array $event): array
    {
        if (!isset($event[LifeCycleToken::KEY_LIFECYCLE_TOKEN])) {
            $event[LifeCycleToken::KEY_LIFECYCLE_TOKEN] = $this->token->toString();
        } elseif (!isset($event['context'][LifeCycleToken::KEY_ORIGINAL_LIFECYCLE_TOKEN])
            && ($this->token->toString() !== $event[LifeCycleToken::KEY_LIFECYCLE_TOKEN])) {
            $event['context'][LifeCycleToken::KEY_ORIGINAL_LIFECYCLE_TOKEN] = $this->token->toString();
        }
        if ($this->token->hasParentToken()) {
            if (!isset($event[LifeCycleToken::KEY_PARENT_LIFECYCLE_TOKEN])) {
                $event[LifeCycleToken::KEY_PARENT_LIFECYCLE_TOKEN] = $this->token->getParentToken()
                    ->toString();
            } elseif (!isset($event['context'][LifeCycleToken::KEY_ORIGINAL_PARENT_LIFECYCLE_TOKEN])
                && $this->token->getParentToken()
                    ->toString() !== $event[LifeCycleToken::KEY_PARENT_LIFECYCLE_TOKEN]) {
                $event['context'][LifeCycleToken::KEY_ORIGINAL_PARENT_LIFECYCLE_TOKEN] = $this->token->getParentToken()
                    ->toString();
            }
        }

        return $event;
    }
}
