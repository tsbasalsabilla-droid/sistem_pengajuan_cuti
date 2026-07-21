<?php

declare(strict_types=1);



namespace Kint\Value;

class ColorValue extends StringValue
{
    public function getHint(): string
    {
        return parent::getHint() ?? 'color';
    }
}
