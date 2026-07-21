<?php

declare(strict_types=1);



namespace CodeIgniter\Encryption;

use CodeIgniter\Encryption\Exceptions\EncryptionException;
use Config\Encryption as EncryptionConfig;


class Encryption
{
    
    protected $encrypter;

    
    protected $driver;

    
    protected $key;

    
    protected $hmacKey;

    
    protected $digest = 'SHA512';

    
    protected $drivers = [
        'OpenSSL',
        'Sodium',
    ];

    
    protected $handlers = [];

    
    public function __construct(?EncryptionConfig $config = null)
    {
        $config ??= new EncryptionConfig();

        $this->key    = $config->key;
        $this->driver = $config->driver;
        $this->digest = $config->digest ?? 'SHA512';

        $this->handlers = [
            'OpenSSL' => extension_loaded('openssl'),
            
            'Sodium' => extension_loaded('sodium') && version_compare(SODIUM_LIBRARY_VERSION, '1.0.14', '>='),
        ];

        if (! in_array($this->driver, $this->drivers, true) || (array_key_exists($this->driver, $this->handlers) && ! $this->handlers[$this->driver])) {
            throw EncryptionException::forNoHandlerAvailable($this->driver);
        }
    }

    
    public function initialize(?EncryptionConfig $config = null)
    {
        if ($config instanceof EncryptionConfig) {
            $this->key    = $config->key;
            $this->driver = $config->driver;
            $this->digest = $config->digest ?? 'SHA512';
        }

        if (empty($this->driver)) {
            throw EncryptionException::forNoDriverRequested();
        }

        if (! in_array($this->driver, $this->drivers, true)) {
            throw EncryptionException::forUnKnownHandler($this->driver);
        }

        if (empty($this->key)) {
            throw EncryptionException::forNeedsStarterKey();
        }

        $this->hmacKey = bin2hex(\hash_hkdf($this->digest, $this->key));

        $handlerName     = 'CodeIgniter\\Encryption\\Handlers\\' . $this->driver . 'Handler';
        $this->encrypter = new $handlerName($config);

        if (($config->previousKeys ?? []) !== []) {
            $this->encrypter = new KeyRotationDecorator($this->encrypter, $config->previousKeys);
        }

        return $this->encrypter;
    }

    
    public static function createKey($length = 32)
    {
        return random_bytes($length);
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
        return in_array($key, ['key', 'digest', 'driver', 'drivers'], true);
    }
}
