<?php

declare(strict_types=1);



namespace CodeIgniter\Security;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\Security\Exceptions\SecurityException;


interface SecurityInterface
{
    
    public function verify(RequestInterface $request);

    
    public function getHash(): ?string;

    
    public function getTokenName(): string;

    
    public function getHeaderName(): string;

    
    public function getCookieName(): string;

    
    public function shouldRedirect(): bool;

    
    public function sanitizeFilename(string $str, bool $relativePath = false): string;
}
