<?php


namespace rollun\logger;


class LifeCycleToken implements \Serializable
{
    //for system token
    const KEY_LIFECYCLE_TOKEN = "lifecycle_token";
    //if sented token not equals to system, system token write with this name
    const KEY_ORIGINAL_LIFECYCLE_TOKEN = "original_lifecycle_token";
    //For parent token
    const KEY_PARENT_LIFECYCLE_TOKEN = "parent_lifecycle_token";
    //if sented parent token not equals to system, system patent token write with this name
    const KEY_ORIGINAL_PARENT_LIFECYCLE_TOKEN = "original_parent_lifecycle_token";

    /**
     * @var string
     */
    private $token;

    /**
     * @var self
     */
    private $parentToken;

    /**
     * Token constructor.
     * @param string $token
     * @param LifeCycleToken $parentToken
     */
    public function __construct(string $token, LifeCycleToken $parentToken = null)
    {
        $this->token = $token;
        $this->parentToken = $parentToken;
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
        $token = new LifeCycleToken(self::IdGenerate(30));
        return $token;
    }

    /**
     * IdGenerator from pollun/utils replacement:
     * Generate id.
     * Generates an arbitrary length string of cryptographic random
     * @param int $nums = 8;
     * @return string
     * @throws \Exception
     */
    public static function IdGenerate($nums = 8)
    {
        /**
         * @var string
         */
        $idCharSet = "QWERTYUIOPASDFGHJKLZXCVBNM0123456789";

        $id = [];
        $idCharSetArray = str_split($idCharSet);
        $charArrayCount = count($idCharSetArray) - 1;
        for ($i = 0; $i < $nums; $i++) {
            $id[$i] = $idCharSetArray[random_int(0, $charArrayCount)];
        }
        $id = implode("", $id);
        return $id;

    }

    /**
     * @see get_all_getders
     */
    public static function getAllHeaders() {
        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach ($_SERVER as $key => $val) {
            if (preg_match($rx_http, $key)) {
                $arh_key = preg_replace($rx_http, '', $key);
                // do some nasty string manipulations to restore the original letter case
                // this should work in most cases
                $rx_matches = explode('_', $arh_key);
                if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                    foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return ($arh);
    }

    /**
     * @return bool
     */
    public function hasParentToken()
    {
        return isset($this->parentToken);
    }

    /**
     * @return LifeCycleToken
     */
    public function getParentToken()
    {
        return $this->parentToken;
    }

    /**
     * Serialize only own token. Parent token not saved (serialized) and not accessibly after serialization.
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return $this->token;
    }

    /**
     * After unserialize token object has changes struct.
     * Serialized token is becoming parent token, and generate new token for onw lifeCycleToken
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        $this->__construct(
            self::generateToken()->toString(),
            new self($serialized)
        );
    }
}