<?php

declare(strict_types=1);



namespace CodeIgniter\Database;


interface QueryInterface
{
    
    public function setQuery(string $sql, mixed $binds = null, bool $setEscape = true): self;

    
    public function getQuery(): string;

    
    public function setDuration(float $start, ?float $end = null): self;

    
    public function getDuration(int $decimals = 6): string;

    
    public function setError(int $code, string $error): self;

    
    public function hasError(): bool;

    
    public function getErrorCode(): int;

    
    public function getErrorMessage(): string;

    
    public function isWriteType(): bool;

    
    public function swapPrefix(string $orig, string $swap): self;

    
    public function getOriginalQuery(): string;
}
