<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;


class Method
{
    
    public const CONNECT = 'CONNECT';

    
    public const DELETE = 'DELETE';

    
    public const GET = 'GET';

    
    public const HEAD = 'HEAD';

    
    public const OPTIONS = 'OPTIONS';

    
    public const PATCH = 'PATCH';

    
    public const POST = 'POST';

    
    public const PUT = 'PUT';

    
    public const TRACE = 'TRACE';

    
    public static function all(): array
    {
        return [
            self::CONNECT,
            self::DELETE,
            self::GET,
            self::HEAD,
            self::OPTIONS,
            self::PATCH,
            self::POST,
            self::PUT,
            self::TRACE,
        ];
    }
}
