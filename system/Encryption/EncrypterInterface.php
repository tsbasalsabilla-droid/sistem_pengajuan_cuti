<?php

declare(strict_types=1);



namespace CodeIgniter\Encryption;

use CodeIgniter\Encryption\Exceptions\EncryptionException;
use SensitiveParameter;


interface EncrypterInterface
{
    
    public function encrypt(#[SensitiveParameter] $data, #[SensitiveParameter] $params = null);

    
    public function decrypt($data, #[SensitiveParameter] $params = null);
}
