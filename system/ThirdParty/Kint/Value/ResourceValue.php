<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Value\Context\ContextInterface;

class ResourceValue extends AbstractValue
{
    
    protected string $resource_type;

    public function __construct(ContextInterface $context, string $resource_type)
    {
        parent::__construct($context, 'resource');
        $this->resource_type = $resource_type;
    }

    public function getDisplayType(): string
    {
        return $this->resource_type.' resource';
    }
}
