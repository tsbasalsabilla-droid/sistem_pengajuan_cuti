<?php

declare(strict_types=1);



namespace Kint\Value\Representation;

use InvalidArgumentException;

class StringRepresentation extends AbstractRepresentation
{
    
    protected string $value;

    
    public function __construct(string $label, string $value, ?string $name = null, bool $implicit = false)
    {
        if ('' === $value) {
            throw new InvalidArgumentException("StringRepresentation can't take empty string");
        }

        parent::__construct($label, $name, $implicit);
        $this->value = $value;
    }

    
    public function getValue(): string
    {
        return $this->value;
    }
}
