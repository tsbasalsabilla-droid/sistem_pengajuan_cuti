<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\HTTP\Exceptions\HTTPException;


interface MessageInterface
{
    
    public function getProtocolVersion(): string;

    
    public function setBody($data);

    
    public function getBody();

    
    public function appendBody($data);

    
    public function populateHeaders(): void;

    
    public function headers(): array;

    
    public function hasHeader(string $name): bool;

    
    public function header($name);

    
    public function getHeaderLine(string $name): string;

    
    public function setHeader(string $name, $value);

    
    public function removeHeader(string $name);

    
    public function appendHeader(string $name, ?string $value);

    
    public function prependHeader(string $name, string $value);

    
    public function setProtocolVersion(string $version);
}
