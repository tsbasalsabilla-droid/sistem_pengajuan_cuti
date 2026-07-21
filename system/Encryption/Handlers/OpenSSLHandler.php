<?php

declare(strict_types=1);



namespace CodeIgniter\Encryption\Handlers;

use CodeIgniter\Encryption\Exceptions\EncryptionException;
use SensitiveParameter;


class OpenSSLHandler extends BaseHandler
{
    
    protected $digest = 'SHA512';

    
    protected array $digestSize = [
        'SHA224' => 28,
        'SHA256' => 32,
        'SHA384' => 48,
        'SHA512' => 64,
    ];

    
    protected $cipher = 'AES-256-CTR';

    
    protected $key = '';

    
    protected bool $rawData = true;

    
    public string $encryptKeyInfo = '';

    
    public string $authKeyInfo = '';

    
    public function encrypt(#[SensitiveParameter] $data, #[SensitiveParameter] $params = null)
    {
        
        $key = $params !== null
            ? (is_array($params) && isset($params['key']) ? $params['key'] : $params)
            : $this->key;

        if (empty($key)) {
            throw EncryptionException::forNeedsStarterKey();
        }

        
        $encryptKey = \hash_hkdf($this->digest, $key, 0, $this->encryptKeyInfo);

        
        $iv = ($ivSize = \openssl_cipher_iv_length($this->cipher)) ? \openssl_random_pseudo_bytes($ivSize) : null;

        $data = \openssl_encrypt($data, $this->cipher, $encryptKey, OPENSSL_RAW_DATA, $iv);

        if ($data === false) {
            throw EncryptionException::forEncryptionFailed();
        }

        $result = $this->rawData ? $iv . $data : base64_encode($iv . $data);

        
        $authKey = \hash_hkdf($this->digest, $key, 0, $this->authKeyInfo);

        $hmacKey = \hash_hmac($this->digest, $result, $authKey, $this->rawData);

        return $hmacKey . $result;
    }

    
    public function decrypt($data, #[SensitiveParameter] $params = null)
    {
        
        $key = $params !== null
            ? (is_array($params) && isset($params['key']) ? $params['key'] : $params)
            : $this->key;

        if (empty($key)) {
            throw EncryptionException::forNeedsStarterKey();
        }

        
        $authKey = \hash_hkdf($this->digest, $key, 0, $this->authKeyInfo);

        $hmacLength = $this->rawData
            ? $this->digestSize[$this->digest]
            : $this->digestSize[$this->digest] * 2;

        $hmacKey  = self::substr($data, 0, $hmacLength);
        $data     = self::substr($data, $hmacLength);
        $hmacCalc = \hash_hmac($this->digest, $data, $authKey, $this->rawData);

        if (! hash_equals($hmacKey, $hmacCalc)) {
            throw EncryptionException::forAuthenticationFailed();
        }

        $data = $this->rawData ? $data : base64_decode($data, true);

        if ($ivSize = \openssl_cipher_iv_length($this->cipher)) {
            $iv   = self::substr($data, 0, $ivSize);
            $data = self::substr($data, $ivSize);
        } else {
            $iv = null;
        }

        
        $encryptKey = \hash_hkdf($this->digest, $key, 0, $this->encryptKeyInfo);

        $result = \openssl_decrypt($data, $this->cipher, $encryptKey, OPENSSL_RAW_DATA, $iv);

        if ($result === false) {
            throw EncryptionException::forAuthenticationFailed();
        }

        return $result;
    }
}
