<?php

namespace rollun\test\logger;

use rollun\logger\LifeCycleToken;
use PHPUnit\Framework\TestCase;

class LifeCycleTokenTest extends TestCase
{

    /** @var LifeCycleToken */
    protected $object;

    public function testToString()
    {
        $tokenString = "TESTLIFECYCLETOKEN";
        $this->object = new LifeCycleToken($tokenString);
        $this->assertEquals($tokenString, $this->object->toString());
    }

    public function testGetParentToken()
    {
        $tokenString = "TESTLIFECYCLETOKEN";
        $parentTokenString = "PARENTTESTLIFECYCLETOKEN";
        $this->object = new LifeCycleToken($tokenString, new LifeCycleToken($parentTokenString));
        $this->assertEquals($tokenString, $this->object->toString());
        $this->assertNotNull($this->object->getParentToken());
        $this->assertEquals($parentTokenString, $this->object->getParentToken()->toString());
    }

    public function testHasParentToken()
    {
        $tokenString = "TESTLIFECYCLETOKEN";
        $parentTokenString = "PARENTTESTLIFECYCLETOKEN";
        $this->object = new LifeCycleToken($tokenString, new LifeCycleToken($parentTokenString));
        $this->assertTrue($this->object->hasParentToken());
    }

    public function testUnserialize()
    {
        $tokenString = "TESTLIFECYCLETOKEN";
        $this->object = new LifeCycleToken($tokenString);
        $serializedToken = $this->object->serialize();
        $this->assertNotEmpty($serializedToken);
    }

    public function testSerialize()
    {
        $tokenString = "TESTLIFECYCLETOKEN";
        $this->object = new LifeCycleToken($tokenString);
        $serializedToken = $this->object->serialize();
        $this->object->unserialize($serializedToken);
        $this->assertNotEquals($tokenString, $this->object->toString());
        $this->assertTrue($this->object->hasParentToken());
        $this->assertNotNull($this->object->getParentToken());
        $this->assertEquals($tokenString, $this->object->getParentToken()->toString());

    }

    public function testGenerateToken()
    {
        $this->object = LifeCycleToken::generateToken();
        $this->assertNotNull($this->object);
        $this->assertNotNull($this->object->toString());
        $this->assertNotEmpty($this->object->toString());
        $this->assertFalse($this->object->hasParentToken());
        $this->assertNull($this->object->getParentToken());
    }
}
