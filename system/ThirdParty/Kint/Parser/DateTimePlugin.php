<?php

declare(strict_types=1);



namespace Kint\Parser;

use DateTimeInterface;
use Error;
use Kint\Value\AbstractValue;
use Kint\Value\DateTimeValue;
use Kint\Value\InstanceValue;

class DateTimePlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if (!$var instanceof DateTimeInterface || !$v instanceof InstanceValue) {
            return $v;
        }

        try {
            $dtv = new DateTimeValue($v->getContext(), $var);
        } catch (Error $e) {
            
            return $v;
        }

        $dtv->setChildren($v->getChildren());
        $dtv->flags = $v->flags;
        $dtv->appendRepresentations($v->getRepresentations());

        return $dtv;
    }
}
