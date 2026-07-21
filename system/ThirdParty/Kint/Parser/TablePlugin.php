<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\ArrayValue;
use Kint\Value\Representation\TableRepresentation;







class TablePlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public static int $max_width = 300;
    public static int $min_width = 2;

    public function getTypes(): array
    {
        return ['array'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if (!$v instanceof ArrayValue) {
            return $v;
        }

        if (\count($var) < 2) {
            return $v;
        }

        
        
        
        $keys = null;
        foreach ($var as $elem) {
            if (!\is_array($elem)) {
                return $v;
            }

            if (null === $keys) {
                if (\count($elem) < self::$min_width || \count($elem) > self::$max_width) {
                    return $v;
                }

                $keys = \array_keys($elem);
            } elseif (\array_keys($elem) !== $keys) {
                return $v;
            }
        }

        $children = $v->getContents();

        if (!$children) {
            return $v;
        }

        
        
        foreach ($children as $childarray) {
            if (!$childarray instanceof ArrayValue || empty($childarray->getContents())) {
                return $v;
            }
        }

        $v->addRepresentation(new TableRepresentation($children), 0);

        return $v;
    }
}
