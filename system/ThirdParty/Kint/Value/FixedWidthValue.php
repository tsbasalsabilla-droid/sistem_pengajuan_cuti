<?php

declare(strict_types=1);



namespace Kint\Value;

use InvalidArgumentException;
use Kint\Value\Context\ContextInterface;


class FixedWidthValue extends AbstractValue
{
    
    protected $value;

    
    public function __construct(ContextInterface $context, $value)
    {
        $type = \strtolower(\gettype($value));

        if ('null' === $type || 'boolean' === $type || 'integer' === $type || 'double' === $type) {
            parent::__construct($context, $type);
            $this->value = $value;
        } else {
            throw new InvalidArgumentException('FixedWidthValue can only contain fixed width types, got '.$type);
        }
    }

    
    public function getValue()
    {
        return $this->value;
    }

    public function getDisplaySize(): ?string
    {
        return null;
    }

    public function getDisplayValue(): ?string
    {
        if ('boolean' === $this->type) {
            return ((bool) $this->value) ? 'true' : 'false';
        }

        if ('integer' === $this->type || 'double' === $this->type) {
            return (string) $this->value;
        }

        return null;
    }
}
