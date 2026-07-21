<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Value\Context\ContextInterface;

class ArrayValue extends AbstractValue
{
    
    protected int $size;
    
    protected array $contents;

    
    public function __construct(ContextInterface $context, int $size, array $contents)
    {
        parent::__construct($context, 'array');
        $this->size = $size;
        $this->contents = $contents;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    
    public function getContents()
    {
        return $this->contents;
    }

    public function getDisplaySize(): string
    {
        return (string) $this->size;
    }

    public function getDisplayChildren(): array
    {
        return $this->contents;
    }
}
