<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger\Filter;

use Traversable;
use rollun\logger\Exception\InvalidArgumentException;
use Laminas\Validator\ValidatorInterface;

class Validator implements FilterInterface
{
    /**
     * Regex to match
     *
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * Filter out any log messages not matching the validator
     *
     * @param  ValidatorInterface|array|Traversable $validator
     * @throws InvalidArgumentException
     */
    public function __construct($validator)
    {
        $this->validator = self::resolveValidator($validator);
    }

    private static function resolveValidator($validator): ValidatorInterface
    {
        if ($validator instanceof ValidatorInterface) {
            return $validator;
        }
        if ($validator instanceof Traversable) {
            $validator = iterator_to_array($validator);
        }
        if (is_array($validator)) {
            $validator = $validator['validator'] ?? null;
        }
        if (!$validator instanceof ValidatorInterface) {
            throw new InvalidArgumentException(sprintf(
                'Parameter of type %s is invalid; must implement Laminas\Validator\ValidatorInterface',
                (is_object($validator) ? get_class($validator) : gettype($validator))
            ));
        }
        return $validator;
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param array $event event data
     * @return bool
     */
    public function filter(array $event)
    {
        return $this->validator->isValid($event['message']);
    }
}
