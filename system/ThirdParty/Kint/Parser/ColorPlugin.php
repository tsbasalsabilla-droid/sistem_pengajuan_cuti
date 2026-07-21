<?php

declare(strict_types=1);



namespace Kint\Parser;

use InvalidArgumentException;
use Kint\Value\AbstractValue;
use Kint\Value\ColorValue;
use Kint\Value\Representation\ColorRepresentation;
use Kint\Value\StringValue;

class ColorPlugin extends AbstractPlugin implements PluginCompleteInterface
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
        if (\strlen($var) > 32) {
            return $v;
        }

        if (!$v instanceof StringValue) {
            return $v;
        }

        $trimmed = \strtolower(\trim($var));

        if (!isset(ColorRepresentation::$color_map[$trimmed]) && !\preg_match('/^(?:(?:rgb|hsl)a?[^\\)]{6,}\\)|#[0-9a-f]{3,8})$/', $trimmed)) {
            return $v;
        }

        try {
            $rep = new ColorRepresentation($var);
        } catch (InvalidArgumentException $e) {
            return $v;
        }

        $out = new ColorValue($v->getContext(), $v->getValue(), $v->getEncoding());
        $out->flags = $v->flags;
        $out->appendRepresentations($v->getRepresentations());
        $out->removeRepresentation('contents');
        $out->addRepresentation($rep, 0);

        return $out;
    }
}
