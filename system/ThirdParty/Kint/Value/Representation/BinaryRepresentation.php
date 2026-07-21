<?php

declare(strict_types=1);



namespace Kint\Value\Representation;

class BinaryRepresentation extends AbstractRepresentation
{
    
    protected string $value;

    public function __construct(string $value, bool $implicit = false)
    {
        parent::__construct('Hex dump', 'binary', $implicit);
        $this->value = $value;
    }

    public function getHint(): string
    {
        return 'binary';
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
