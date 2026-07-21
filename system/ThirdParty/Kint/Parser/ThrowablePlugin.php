<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\InstanceValue;
use Kint\Value\Representation\SourceRepresentation;
use Kint\Value\ThrowableValue;
use RuntimeException;
use Throwable;

class ThrowablePlugin extends AbstractPlugin implements PluginCompleteInterface
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
        if (!$var instanceof Throwable || !$v instanceof InstanceValue) {
            return $v;
        }

        $throw = new ThrowableValue($v->getContext(), $var);
        $throw->setChildren($v->getChildren());
        $throw->flags = $v->flags;
        $throw->appendRepresentations($v->getRepresentations());

        try {
            $throw->addRepresentation(new SourceRepresentation($var->getFile(), $var->getLine(), null, true), 0);
        } catch (RuntimeException $e) {
        }

        return $throw;
    }
}
