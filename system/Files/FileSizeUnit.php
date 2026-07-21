<?php

declare(strict_types=1);



namespace CodeIgniter\Files;

use CodeIgniter\Exceptions\InvalidArgumentException;

enum FileSizeUnit: int
{
    case B  = 0;
    case KB = 1;
    case MB = 2;
    case GB = 3;
    case TB = 4;

    
    public static function fromString(string $unit): self
    {
        return match (strtolower($unit)) {
            'b'     => self::B,
            'kb'    => self::KB,
            'mb'    => self::MB,
            'gb'    => self::GB,
            'tb'    => self::TB,
            default => throw new InvalidArgumentException("Invalid unit: {$unit}"),
        };
    }
}
