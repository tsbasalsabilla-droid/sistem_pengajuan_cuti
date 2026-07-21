<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Representation\BinaryRepresentation;
use Kint\Value\StringValue;

class BinaryPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public function getTypes(): array
    {
        return ['string'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if ($v instanceof StringValue && false === $v->getEncoding()) {
            $v->addRepresentation(new BinaryRepresentation($v->getValue(), true), 0);
        }

        return $v;
    }
}
