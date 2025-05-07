<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Logger\Processor;

use rollun\logger\Processor\LifeCycleTokenInjector;
use PHPUnit\Framework\TestCase;
use rollun\logger\LifeCycleToken;

class LifeCycleTokenInjectorTest extends TestCase
{
    public function testInjectToken()
    {
        $lifeCycleToken = LifeCycleToken::generateToken();
        $lifeCycleTokenInjector = new LifeCycleTokenInjector($lifeCycleToken);


        $event = [];
        $event = $lifeCycleTokenInjector->process($event);
        $this->assertEquals($lifeCycleToken->toString(), $event[LifeCycleToken::KEY_LIFECYCLE_TOKEN]);
    }

    public function testInjectWithParentToken()
    {
        $lifeCycleTokenParent = LifeCycleToken::generateToken();
        $lifeCycleToken = new LifeCycleToken(LifeCycleToken::generateToken()
            ->toString(), $lifeCycleTokenParent);
        $lifeCycleTokenInjector = new LifeCycleTokenInjector($lifeCycleToken);

        $event = [];
        $event = $lifeCycleTokenInjector->process($event);
        $this->assertEquals($lifeCycleToken->toString(), $event[LifeCycleToken::KEY_LIFECYCLE_TOKEN]);
        $this->assertEquals($lifeCycleTokenParent->toString(), $event[LifeCycleToken::KEY_PARENT_LIFECYCLE_TOKEN]);
    }

    public function testTokenIsExist()
    {
        $lifeCycleToken = LifeCycleToken::generateToken();
        $lifeCycleTokenInjector = new LifeCycleTokenInjector($lifeCycleToken);

        $tokenId = LifeCycleToken::generateToken()
            ->toString();
        $event = [
            LifeCycleToken::KEY_LIFECYCLE_TOKEN => $tokenId,
        ];
        $event = $lifeCycleTokenInjector->process($event);
        $this->assertEquals($tokenId, $event[LifeCycleToken::KEY_LIFECYCLE_TOKEN]);
        $this->assertEquals(
            $lifeCycleToken->toString(),
            $event['context'][LifeCycleToken::KEY_ORIGINAL_LIFECYCLE_TOKEN]
        );
    }

    public function testTokenIsExistButEq()
    {
        $lifeCycleToken = LifeCycleToken::generateToken();
        $lifeCycleTokenInjector = new LifeCycleTokenInjector($lifeCycleToken);

        $event = [
            LifeCycleToken::KEY_LIFECYCLE_TOKEN => $lifeCycleToken->toString(),
        ];
        $event = $lifeCycleTokenInjector->process($event);
        $this->assertEquals($lifeCycleToken->toString(), $event[LifeCycleToken::KEY_LIFECYCLE_TOKEN]);
        $this->assertArrayNotHasKey('context', $event);
    }

    public function testParentTokenIsExist()
    {
        $lifeCycleTokenParent = LifeCycleToken::generateToken();
        $lifeCycleToken = new LifeCycleToken(LifeCycleToken::generateToken()
            ->toString(), $lifeCycleTokenParent);
        $lifeCycleTokenInjector = new LifeCycleTokenInjector($lifeCycleToken);

        $parentTokenId = LifeCycleToken::generateToken()
            ->toString();
        $event = [
            LifeCycleToken::KEY_PARENT_LIFECYCLE_TOKEN => $parentTokenId,
        ];
        $event = $lifeCycleTokenInjector->process($event);
        $this->assertEquals($parentTokenId, $event[LifeCycleToken::KEY_PARENT_LIFECYCLE_TOKEN]);
        $this->assertEquals(
            $lifeCycleTokenParent->toString(),
            $event['context'][LifeCycleToken::KEY_ORIGINAL_PARENT_LIFECYCLE_TOKEN]
        );
    }
}
