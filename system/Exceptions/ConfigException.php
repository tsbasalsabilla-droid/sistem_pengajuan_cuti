<?php

declare(strict_types=1);



namespace CodeIgniter\Exceptions;


class ConfigException extends RuntimeException implements HasExitCodeInterface
{
    use DebugTraceableTrait;

    public function getExitCode(): int
    {
        return EXIT_CONFIG;
    }

    
    public static function forDisabledMigrations()
    {
        return new static(lang('Migrations.disabled'));
    }
}
