<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\test\logger\Filter;

use PHPUnit\Framework\TestCase;
use rollun\logger\Filter\Validator;
use Laminas\Validator\ValidatorChain;
use Laminas\Validator\Digits as DigitsFilter;
use Laminas\Validator\NotEmpty as NotEmptyFilter;

class ValidatorTest extends TestCase
{
    public function testValidatorFilter()
    {
        $filter = new Validator(new DigitsFilter());
        $this->assertTrue($filter->filter(['message' => '123']));
        $this->assertFalse($filter->filter(['message' => 'test']));
        $this->assertFalse($filter->filter(['message' => 'test123']));
        $this->assertFalse($filter->filter(['message' => '(%$']));
    }

    public function testValidatorChain()
    {
        $validatorChain = new ValidatorChain();
        $validatorChain->attach(new NotEmptyFilter());
        $validatorChain->attach(new DigitsFilter());
        $filter = new Validator($validatorChain);
        $this->assertTrue($filter->filter(['message' => '123']));
        $this->assertFalse($filter->filter(['message' => 'test']));
    }
}
