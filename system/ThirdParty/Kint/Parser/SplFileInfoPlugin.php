<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\InstanceValue;
use Kint\Value\Representation\SplFileInfoRepresentation;
use Kint\Value\SplFileInfoValue;
use SplFileInfo;
use SplFileObject;

class SplFileInfoPlugin extends AbstractPlugin implements PluginCompleteInterface
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
        
        if (!$var instanceof SplFileInfo || $var instanceof SplFileObject) {
            return $v;
        }

        if (!$v instanceof InstanceValue) {
            return $v;
        }

        $out = new SplFileInfoValue($v->getContext(), $var);
        $out->setChildren($v->getChildren());
        $out->flags = $v->flags;
        $out->addRepresentation(new SplFileInfoRepresentation(clone $var));
        $out->appendRepresentations($v->getRepresentations());

        return $out;
    }
}
