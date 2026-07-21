<?php

declare(strict_types=1);



namespace CodeIgniter\Encryption\Handlers;

use CodeIgniter\Encryption\EncrypterInterface;
use Config\Encryption;


abstract class BaseHandler implements EncrypterInterface
{
    
    public function __construct(?Encryption $config = null)
    {
        $config ??= config(Encryption::class);

        
        foreach (get_object_vars($config) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    
    protected static function substr($str, $start, $length = null)
    {
        return mb_substr($str, $start, $length, '8bit');
    }

    
    public function __get($key)
    {
        if ($this->__isset($key)) {
            return $this->{$key};
        }

        return null;
    }

    
    public function __isset($key): bool
    {
        return property_exists($this, $key);
    }
}
