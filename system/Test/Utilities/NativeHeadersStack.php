<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Utilities;


final class NativeHeadersStack
{
    
    public static bool $headersSent = false;

    
    public static array $headers = [];

    
    public static function reset(): void
    {
        self::$headersSent = false;
        self::$headers     = [];
    }

    
    public static function has(string $header): bool
    {
        return in_array($header, self::$headers, true);
    }

    
    public static function push(string $header): void
    {
        self::$headers[] = $header;
    }
}
