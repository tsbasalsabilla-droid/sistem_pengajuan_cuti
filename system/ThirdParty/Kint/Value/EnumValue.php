<?php

declare(strict_types=1);



namespace Kint\Value;

use BackedEnum;
use Kint\Value\Context\ContextInterface;
use UnitEnum;

class EnumValue extends InstanceValue
{
    
    protected UnitEnum $enumval;

    public function __construct(ContextInterface $context, UnitEnum $enumval)
    {
        parent::__construct($context, \get_class($enumval), \spl_object_hash($enumval), \spl_object_id($enumval));

        $this->enumval = $enumval;
    }

    public function getHint(): string
    {
        return parent::getHint() ?? 'enum';
    }

    public function getDisplayType(): string
    {
        return $this->classname.'::'.$this->enumval->name;
    }

    public function getDisplayValue(): ?string
    {
        if ($this->enumval instanceof BackedEnum) {
            if (\is_string($this->enumval->value)) {
                return '"'.$this->enumval->value.'"';
            }

            return (string) $this->enumval->value;
        }

        return null;
    }
}
