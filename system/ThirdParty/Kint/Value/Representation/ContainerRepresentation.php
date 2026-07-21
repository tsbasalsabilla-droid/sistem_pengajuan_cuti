<?php

declare(strict_types=1);



namespace Kint\Value\Representation;

use InvalidArgumentException;
use Kint\Value\AbstractValue;

class ContainerRepresentation extends AbstractRepresentation
{
    
    protected array $contents;

    
    public function __construct(string $label, array $contents, ?string $name = null, bool $implicit_label = false)
    {
        if ([] === $contents) {
            throw new InvalidArgumentException("ContainerRepresentation can't take empty list");
        }

        parent::__construct($label, $name, $implicit_label);
        $this->contents = $contents;
    }

    
    public function getContents(): array
    {
        return $this->contents;
    }

    public function getLabel(): string
    {
        return parent::getLabel().' ('.\count($this->contents).')';
    }
}
