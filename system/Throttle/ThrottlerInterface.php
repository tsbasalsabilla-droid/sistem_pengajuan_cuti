<?php

declare(strict_types=1);



namespace CodeIgniter\Throttle;


interface ThrottlerInterface
{
    
    public function check(string $key, int $capacity, int $seconds, int $cost);

    
    public function getTokenTime(): int;
}
