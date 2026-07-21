<?php

declare(strict_types=1);



namespace Kint\Value\Context;

use InvalidArgumentException;


class BaseContext implements ContextInterface
{
    
    public $name;
    public int $depth = 0;
    public bool $reference = false;
    public ?string $access_path = null;

    
    public function __construct($name)
    {
        if (!\is_string($name) && !\is_int($name)) {
            throw new InvalidArgumentException('Context names must be string|int');
        }

        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getOperator(): ?string
    {
        return null;
    }

    public function isRef(): bool
    {
        return $this->reference;
    }

    
    public function isAccessible(?string $scope): bool
    {
        return true;
    }

    public function getAccessPath(): ?string
    {
        return $this->access_path;
    }
}
