<?php

declare(strict_types=1);



namespace Kint\Value\Representation;

use Kint\Value\AbstractValue;

class ValueRepresentation extends AbstractRepresentation
{
    
    protected AbstractValue $value;

    public function __construct(string $label, AbstractValue $value, ?string $name = null, bool $implicit_label = false)
    {
        parent::__construct($label, $name, $implicit_label);
        $this->value = $value;
    }

    public function getValue(): AbstractValue
    {
        return $this->value;
    }
}
