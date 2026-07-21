<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\EnumValue;
use Kint\Value\Representation\ContainerRepresentation;
use UnitEnum;

class EnumPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    private array $cache = [];

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        if (!KINT_PHP81) {
            return Parser::TRIGGER_NONE;
        }

        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if (!$var instanceof UnitEnum) {
            return $v;
        }

        $c = $v->getContext();
        $class = \get_class($var);

        if (!isset($this->cache[$class])) {
            $contents = [];

            foreach ($var->cases() as $case) {
                $base = new BaseContext($case->name);
                $base->access_path = '\\'.$class.'::'.$case->name;
                $base->depth = $c->getDepth() + 1;
                $contents[] = new EnumValue($base, $case);
            }

            
            $this->cache[$class] = new ContainerRepresentation('Enum values', $contents, 'enum');
        }

        $object = new EnumValue($c, $var);
        $object->flags = $v->flags;
        $object->appendRepresentations($v->getRepresentations());
        $object->addRepresentation($this->cache[$class], 0);

        return $object;
    }
}
