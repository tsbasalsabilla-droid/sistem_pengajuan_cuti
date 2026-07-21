<?php

declare(strict_types=1);



namespace CodeIgniter\Session;


interface SessionInterface
{
    
    public function regenerate(bool $destroy = false);

    
    public function destroy();

    
    public function set($data, $value = null);

    
    public function get(?string $key = null);

    
    public function has(string $key): bool;

    
    public function remove($key);

    
    public function setFlashdata($data, $value = null);

    
    public function getFlashdata(?string $key = null);

    
    public function keepFlashdata($key);

    
    public function markAsFlashdata($key);

    
    public function unmarkFlashdata($key);

    
    public function getFlashKeys(): array;

    
    public function setTempdata($data, $value = null, int $ttl = 300);

    
    public function getTempdata(?string $key = null);

    
    public function removeTempdata(string $key);

    
    public function markAsTempdata($key, int $ttl = 300);

    
    public function unmarkTempdata($key);

    
    public function getTempKeys(): array;
}
