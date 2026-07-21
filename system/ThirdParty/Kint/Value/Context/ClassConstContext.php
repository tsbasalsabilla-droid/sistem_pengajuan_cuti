<?php

declare(strict_types=1);



namespace Kint\Value\Context;

class ClassConstContext extends ClassDeclaredContext
{
    public bool $final = false;

    public function getName(): string
    {
        return $this->owner_class.'::'.$this->name;
    }

    public function getOperator(): string
    {
        return '::';
    }

    public function getModifiers(): string
    {
        $final = $this->final ? 'final ' : '';

        return $final.$this->getAccess().' const';
    }
}
