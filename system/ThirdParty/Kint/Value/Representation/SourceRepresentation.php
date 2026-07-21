<?php

declare(strict_types=1);



namespace Kint\Value\Representation;

use RuntimeException;

class SourceRepresentation extends AbstractRepresentation
{
    private const DEFAULT_PADDING = 7;

    
    protected array $source;
    
    protected string $filename;
    
    protected int $line;
    
    protected bool $showfilename;

    public function __construct(string $filename, int $line, ?int $padding = self::DEFAULT_PADDING, bool $showfilename = false)
    {
        parent::__construct('Source');

        $this->filename = $filename;
        $this->line = $line;
        $this->showfilename = $showfilename;

        $padding ??= self::DEFAULT_PADDING;

        $start_line = \max($line - $padding, 1);
        $length = $line + $padding + 1 - $start_line;
        $this->source = self::readSource($filename, $start_line, $length);
    }

    public function getHint(): string
    {
        return 'source';
    }

    
    public function getSourceSlice(): string
    {
        return \implode("\n", $this->source);
    }

    
    public function getSourceLines(): array
    {
        return $this->source;
    }

    public function getFileName(): string
    {
        return $this->filename;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function showFileName(): bool
    {
        return $this->showfilename;
    }

    
    private static function readSource(string $filename, int $start_line = 1, ?int $length = null): array
    {
        if (!$filename || !\file_exists($filename) || !\is_readable($filename)) {
            throw new RuntimeException("Couldn't read file");
        }

        
        $source = \preg_split("/\r\n|\n|\r/", (string) \file_get_contents($filename));
        
        $source = \array_combine(\range(1, \count($source)), $source);
        $source = \array_slice($source, $start_line - 1, $length, true);

        if (0 === \count($source)) {
            throw new RuntimeException('File seemed to be empty');
        }

        return $source;
    }
}
