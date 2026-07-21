<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Representation\ValueRepresentation;
use Kint\Value\StringValue;

class Base64Plugin extends AbstractPlugin implements PluginCompleteInterface
{
    
    public static int $min_length_hard = 16;

    
    public static int $min_length_soft = 50;

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
        if (\strlen($var) < self::$min_length_hard || \strlen($var) % 4) {
            return $v;
        }

        if (\preg_match('/^[A-Fa-f0-9]+$/', $var)) {
            return $v;
        }

        if (!\preg_match('/^[A-Za-z0-9+\\/=]+$/', $var)) {
            return $v;
        }

        $data = \base64_decode($var, true);

        if (false === $data) {
            return $v;
        }

        $c = $v->getContext();

        $base = new BaseContext('base64_decode('.$c->getName().')');
        $base->depth = $c->getDepth() + 1;

        if (null !== ($ap = $c->getAccessPath())) {
            $base->access_path = 'base64_decode('.$ap.')';
        }

        $data = $this->getParser()->parse($data, $base);
        $data->flags |= AbstractValue::FLAG_GENERATED;

        if (!$data instanceof StringValue || false === $data->getEncoding()) {
            return $v;
        }

        $r = new ValueRepresentation('Base64', $data);

        if (\strlen($var) > self::$min_length_soft) {
            $v->addRepresentation($r, 0);
        } else {
            $v->addRepresentation($r);
        }

        return $v;
    }
}
