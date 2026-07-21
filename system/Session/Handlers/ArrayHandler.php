<?php

declare(strict_types=1);



namespace CodeIgniter\Session\Handlers;


class ArrayHandler extends BaseHandler
{
    
    protected static $cache = [];

    
    public function open($path, $name): bool
    {
        return true;
    }

    
    public function read($id): string
    {
        return '';
    }

    
    public function write($id, $data): bool
    {
        return true;
    }

    
    public function close(): bool
    {
        return true;
    }

    
    public function destroy($id): bool
    {
        return true;
    }

    
    public function gc($max_lifetime): int
    {
        return 1;
    }
}
