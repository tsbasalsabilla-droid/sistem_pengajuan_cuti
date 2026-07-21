<?php

declare(strict_types=1);



namespace CodeIgniter\Format;

use CodeIgniter\Format\Exceptions\FormatException;
use Config\Format as FormatConfig;


class Format
{
    public function __construct(protected FormatConfig $config)
    {
    }

    
    public function getConfig()
    {
        return $this->config;
    }

    
    public function getFormatter(string $mime): FormatterInterface
    {
        if (! array_key_exists($mime, $this->config->formatters)) {
            throw FormatException::forInvalidMime($mime);
        }

        $className = $this->config->formatters[$mime];

        if (! class_exists($className)) {
            throw FormatException::forInvalidFormatter($className);
        }

        $class = new $className();

        if (! $class instanceof FormatterInterface) {
            throw FormatException::forInvalidFormatter($className);
        }

        return $class;
    }
}
