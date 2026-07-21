<?php

declare(strict_types=1);



namespace Kint\Value\Context;

class ArrayContext extends BaseContext
{
    public function getOperator(): ?string
    {
        return '=>';
    }
}
