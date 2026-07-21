<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Value\Context\ContextInterface;

class UnknownValue extends AbstractValue
{
    public function __construct(ContextInterface $context)
    {
        parent::__construct($context, 'unknown');
    }
}
