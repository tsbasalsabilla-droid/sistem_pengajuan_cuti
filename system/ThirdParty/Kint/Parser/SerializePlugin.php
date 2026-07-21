<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Representation\ValueRepresentation;
use Kint\Value\UninitializedValue;


class SerializePlugin extends AbstractPlugin implements PluginCompleteInterface
{
    
    public static bool $safe_mode = true;

    
    public static $allowed_classes = false;

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
        $trimmed = \rtrim($var);

        if ('N;' !== $trimmed && !\preg_match('/^(?:[COabis]:\\d+[:;]|d:\\d+(?:\\.\\d+);)/', $trimmed)) {
            return $v;
        }

        $options = ['allowed_classes' => self::$allowed_classes];

        $c = $v->getContext();

        $base = new BaseContext('unserialize('.$c->getName().')');
        $base->depth = $c->getDepth() + 1;

        if (null !== ($ap = $c->getAccessPath())) {
            $base->access_path = 'unserialize('.$ap;
            if (true === self::$allowed_classes) {
                $base->access_path .= ')';
            } else {
                $base->access_path .= ', '.\var_export($options, true).')';
            }
        }

        if (self::$safe_mode && \in_array($trimmed[0], ['C', 'O', 'a'], true)) {
            $data = new UninitializedValue($base);
            $data->flags |= AbstractValue::FLAG_BLACKLIST;
        } else {
            
            $data = @\unserialize($trimmed, $options);

            if (false === $data && 'b:0;' !== \substr($trimmed, 0, 4)) {
                return $v;
            }

            $data = $this->getParser()->parse($data, $base);
        }

        $data->flags |= AbstractValue::FLAG_GENERATED;

        $v->addRepresentation(new ValueRepresentation('Serialized', $data), 0);

        return $v;
    }
}
