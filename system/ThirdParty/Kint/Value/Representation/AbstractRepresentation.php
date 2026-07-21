<?php

declare(strict_types=1);



namespace Kint\Value\Representation;

abstract class AbstractRepresentation implements RepresentationInterface
{
    
    protected string $label;
    
    protected string $name;
    
    protected bool $implicit_label;

    public function __construct(string $label, ?string $name = null, bool $implicit_label = false)
    {
        $this->label = $label;
        
        $name = \preg_replace('/[^a-z0-9]+/', '_', \strtolower($name ?? $label));
        $this->name = $name;
        $this->implicit_label = $implicit_label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function labelIsImplicit(): bool
    {
        return $this->implicit_label;
    }

    public function getHint(): ?string
    {
        return null;
    }
}
