<?php

declare(strict_types=1);



namespace Kint\Value;

use Dom\Node;
use DOMNode;
use Kint\Value\Context\ContextInterface;

class DomNodeValue extends InstanceValue
{
    
    public function __construct(ContextInterface $context, object $node)
    {
        parent::__construct($context, \get_class($node), \spl_object_hash($node), \spl_object_id($node));
    }

    public function getDisplaySize(): ?string
    {
        return null;
    }
}
