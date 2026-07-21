<?php

declare(strict_types=1);



namespace Kint\Value;

class MicrotimeValue extends AbstractValue
{
    
    protected AbstractValue $wrapped;

    public function __construct(AbstractValue $old)
    {
        $this->context = $old->context;
        $this->type = $old->type;
        $this->flags = $old->flags;
        $this->representations = $old->representations;
        $this->wrapped = $old;
    }

    public function getHint(): string
    {
        return parent::getHint() ?? 'microtime';
    }

    public function getDisplaySize(): ?string
    {
        return $this->wrapped->getDisplaySize();
    }

    public function getDisplayValue(): ?string
    {
        return $this->wrapped->getDisplayValue();
    }

    public function getDisplayType(): string
    {
        return $this->wrapped->getDisplayType();
    }
}
