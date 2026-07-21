<?php

declare(strict_types=1);



namespace Kint\Value;

use Dom\NodeList;
use DOMNodeList;
use Kint\Value\Context\ContextInterface;

class DomNodeListValue extends InstanceValue
{
    protected int $length;

    
    public function __construct(ContextInterface $context, object $node)
    {
        parent::__construct($context, \get_class($node), \spl_object_hash($node), \spl_object_id($node));

        $this->length = $node->length;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getDisplaySize(): string
    {
        return (string) $this->length;
    }
}
