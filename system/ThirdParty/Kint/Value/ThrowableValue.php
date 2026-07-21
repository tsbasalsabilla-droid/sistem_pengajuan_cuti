<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Value\Context\ContextInterface;
use Throwable;

class ThrowableValue extends InstanceValue
{
    
    protected string $message;

    public function __construct(ContextInterface $context, Throwable $throw)
    {
        parent::__construct($context, \get_class($throw), \spl_object_hash($throw), \spl_object_id($throw));

        $this->message = $throw->getMessage();
    }

    public function getDisplayValue(): string
    {
        return '"'.$this->message.'"';
    }
}
