<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Value\Context\ContextInterface;

class ClosedResourceValue extends AbstractValue
{
    public function __construct(ContextInterface $context)
    {
        parent::__construct($context, 'resource (closed)');
    }

    public function getDisplayType(): string
    {
        return 'closed resource';
    }
}
