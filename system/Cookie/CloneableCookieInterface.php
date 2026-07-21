<?php

declare(strict_types=1);



namespace CodeIgniter\Cookie;

use DateTimeInterface;


interface CloneableCookieInterface extends CookieInterface
{
    
    public function withPrefix(string $prefix = '');

    
    public function withName(string $name);

    
    public function withValue(string $value);

    
    public function withExpires($expires);

    
    public function withExpired();

    
    public function withPath(?string $path);

    
    public function withDomain(?string $domain);

    
    public function withSecure(bool $secure = true);

    
    public function withHTTPOnly(bool $httponly = true);

    
    public function withSameSite(string $samesite);

    
    public function withRaw(bool $raw = true);
}
