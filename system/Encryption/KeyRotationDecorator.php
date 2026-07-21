<?php

declare(strict_types=1);



namespace CodeIgniter\Encryption;

use CodeIgniter\Encryption\Exceptions\EncryptionException;
use SensitiveParameter;


class KeyRotationDecorator implements EncrypterInterface
{
    
    public function __construct(
        private  EncrypterInterface $innerHandler,
        private  array $previousKeys,
    ) {
    }

    
    public function encrypt(#[SensitiveParameter] $data, #[SensitiveParameter] $params = null)
    {
        return $this->innerHandler->encrypt($data, $params);
    }

    
    public function decrypt($data, #[SensitiveParameter] $params = null)
    {
        try {
            return $this->innerHandler->decrypt($data, $params);
        } catch (EncryptionException $e) {
            
            if (is_string($params) || (is_array($params) && isset($params['key']))) {
                throw $e;
            }

            if ($this->previousKeys === []) {
                throw $e;
            }

            foreach ($this->previousKeys as $previousKey) {
                try {
                    $previousParams = is_array($params)
                        ? array_merge($params, ['key' => $previousKey])
                        : $previousKey;

                    return $this->innerHandler->decrypt($data, $previousParams);
                } catch (EncryptionException) {
                    continue;
                }
            }

            throw $e;
        }
    }

    
    public function __get(string $key)
    {
        if (method_exists($this->innerHandler, '__get')) {
            return $this->innerHandler->__get($key);
        }

        return null;
    }

    
    public function __isset(string $key): bool
    {
        if (method_exists($this->innerHandler, '__isset')) {
            return $this->innerHandler->__isset($key);
        }

        return false;
    }
}
