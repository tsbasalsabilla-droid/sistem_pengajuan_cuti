<?php

declare(strict_types=1);



namespace CodeIgniter\Exceptions;


class TestException extends LogicException
{
    use DebugTraceableTrait;

    
    public static function forInvalidMockClass(string $name)
    {
        return new static(lang('Test.invalidMockClass', [$name]));
    }
}
