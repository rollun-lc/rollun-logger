<?php


namespace rollun\token;


use rollun\utils\IdGenerator;

class LifeCycleToken
{
    const KEY_PARENT_LIFECYCLE_TOKEN = "parent_lifecycle_token";

    /**
     * @var string
     */
    private $token;

    /**
     * Token constructor.
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * Generate token with 30 chars length.
     */
    public static function generateToken()
    {
        $idGenerator = new IdGenerator(30);
        $token = new LifeCycleToken($idGenerator->generate());
        return $token;
    }
}