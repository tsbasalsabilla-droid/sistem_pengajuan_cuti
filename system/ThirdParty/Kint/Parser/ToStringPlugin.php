<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Representation\ValueRepresentation;
use ReflectionClass;
use SimpleXMLElement;
use SplFileInfo;
use Throwable;

class ToStringPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public static array $blacklist = [
        SimpleXMLElement::class,
        SplFileInfo::class,
    ];

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
        $reflection = new ReflectionClass($var);
        if (!$reflection->hasMethod('__toString')) {
            return $v;
        }

        foreach (self::$blacklist as $class) {
            if ($var instanceof $class) {
                return $v;
            }
        }

        try {
            $string = (string) $var;
        } catch (Throwable $t) {
            return $v;
        }

        $c = $v->getContext();

        $base = new BaseContext($c->getName());
        $base->depth = $c->getDepth() + 1;
        if (null !== ($ap = $c->getAccessPath())) {
            $base->access_path = '(string) '.$ap;
        }

        $string = $this->getParser()->parse($string, $base);

        $v->addRepresentation(new ValueRepresentation('toString', $string));

        return $v;
    }
}
