<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Value\Context\ContextInterface;

class InstanceValue extends AbstractValue
{
    
    protected string $classname;
    
    protected string $spl_object_hash;
    
    protected int $spl_object_id;

    
    protected ?array $children = null;

    
    public function __construct(
        ContextInterface $context,
        string $classname,
        string $spl_object_hash,
        int $spl_object_id
    ) {
        parent::__construct($context, 'object');
        $this->classname = $classname;
        $this->spl_object_hash = $spl_object_hash;
        $this->spl_object_id = $spl_object_id;
    }

    
    public function getClassName(): string
    {
        return $this->classname;
    }

    public function getSplObjectHash(): string
    {
        return $this->spl_object_hash;
    }

    public function getSplObjectId(): int
    {
        return $this->spl_object_id;
    }

    
    public function setChildren(?array $children): void
    {
        $this->children = $children;
    }

    
    public function getChildren(): ?array
    {
        return $this->children;
    }

    public function getDisplayType(): string
    {
        return $this->classname;
    }

    public function getDisplaySize(): ?string
    {
        if (null === $this->children) {
            return null;
        }

        return (string) \count($this->children);
    }

    public function getDisplayChildren(): array
    {
        return $this->children ?? [];
    }
}
