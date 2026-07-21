<?php

declare(strict_types=1);



namespace CodeIgniter\Encryption\Handlers;

use CodeIgniter\Encryption\Exceptions\EncryptionException;
use SensitiveParameter;


class SodiumHandler extends BaseHandler
{
    
    protected $key = '';

    
    protected $blockSize = 16;

    
    public function encrypt(#[SensitiveParameter] $data, #[SensitiveParameter] $params = null)
    {
        
        $key = $params !== null
            ? (is_array($params) && isset($params['key']) ? $params['key'] : $params)
            : $this->key;

        
        $blockSize = (is_array($params) && isset($params['blockSize']))
            ? $params['blockSize']
            : $this->blockSize;

        if (empty($key)) {
            throw EncryptionException::forNeedsStarterKey();
        }

        
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES); 

        
        if ($blockSize <= 0) {
            throw EncryptionException::forEncryptionFailed();
        }

        $data = sodium_pad($data, $blockSize);

        
        $ciphertext = $nonce . sodium_crypto_secretbox($data, $nonce, $key);

        
        sodium_memzero($data);
        sodium_memzero($key);

        return $ciphertext;
    }

    
    public function decrypt($data, #[SensitiveParameter] $params = null)
    {
        
        $key = $params !== null
            ? (is_array($params) && isset($params['key']) ? $params['key'] : $params)
            : $this->key;

        
        $blockSize = (is_array($params) && isset($params['blockSize']))
            ? $params['blockSize']
            : $this->blockSize;

        if (empty($key)) {
            throw EncryptionException::forNeedsStarterKey();
        }

        if (mb_strlen($data, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            
            throw EncryptionException::forAuthenticationFailed();
        }

        
        $nonce      = self::substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = self::substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        
        $data = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

        if ($data === false) {
            
            throw EncryptionException::forAuthenticationFailed(); 
        }

        
        if ($blockSize <= 0) {
            throw EncryptionException::forAuthenticationFailed();
        }

        $data = sodium_unpad($data, $blockSize);

        
        sodium_memzero($ciphertext);
        sodium_memzero($key);

        return $data;
    }

    
    protected function parseParams($params)
    {
        if ($params === null) {
            return;
        }

        if (is_array($params)) {
            if (isset($params['key'])) {
                $this->key = $params['key'];
            }

            if (isset($params['blockSize'])) {
                $this->blockSize = $params['blockSize'];
            }

            return;
        }

        $this->key = (string) $params;
    }
}
