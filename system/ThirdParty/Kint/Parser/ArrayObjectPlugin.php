<?php

declare(strict_types=1);



namespace Kint\Parser;

use ArrayObject;
use Kint\Value\AbstractValue;
use Kint\Value\Context\ContextInterface;

class ArrayObjectPlugin extends AbstractPlugin implements PluginBeginInterface
{
    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_BEGIN;
    }

    public function parseBegin(&$var, ContextInterface $c): ?AbstractValue
    {
        if (!$var instanceof ArrayObject) {
            return null;
        }

        $flags = $var->getFlags();

        if (ArrayObject::STD_PROP_LIST === $flags) {
            return null;
        }

        $parser = $this->getParser();

        $var->setFlags(ArrayObject::STD_PROP_LIST);

        $v = $parser->parse($var, $c);

        $var->setFlags($flags);

        return $v;
    }
}
