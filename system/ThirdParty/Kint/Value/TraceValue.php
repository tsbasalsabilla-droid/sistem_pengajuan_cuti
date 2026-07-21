<?php

declare(strict_types=1);



namespace Kint\Value;

class TraceValue extends ArrayValue
{
    public function getHint(): string
    {
        return parent::getHint() ?? 'trace';
    }

    public function getDisplayType(): string
    {
        return 'Debug Backtrace';
    }

    public function getDisplaySize(): string
    {
        if ($this->size > 0) {
            return parent::getDisplaySize();
        }

        return 'empty';
    }
}
