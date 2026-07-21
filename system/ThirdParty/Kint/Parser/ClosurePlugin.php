<?php

declare(strict_types=1);



namespace Kint\Parser;

use Closure;
use Kint\Value\AbstractValue;
use Kint\Value\ClosureValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Representation\ContainerRepresentation;
use ReflectionFunction;
use ReflectionReference;

class ClosurePlugin extends AbstractPlugin implements PluginCompleteInterface
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
        if (!$var instanceof Closure) {
            return $v;
        }

        $c = $v->getContext();

        $object = new ClosureValue($c, $var);
        $object->flags = $v->flags;
        $object->appendRepresentations($v->getRepresentations());

        $object->removeRepresentation('properties');

        $closure = new ReflectionFunction($var);

        $statics = [];

        if ($v = $closure->getClosureThis()) {
            $statics = ['this' => $v];
        }

        $statics = $statics + $closure->getStaticVariables();

        $cdepth = $c->getDepth();

        if (\count($statics)) {
            $statics_parsed = [];

            $parser = $this->getParser();

            foreach ($statics as $name => $_) {
                $base = new BaseContext('$'.$name);
                $base->depth = $cdepth + 1;
                $base->reference = null !== ReflectionReference::fromArrayElement($statics, $name);
                $statics_parsed[$name] = $parser->parse($statics[$name], $base);
            }

            $object->addRepresentation(new ContainerRepresentation('Uses', $statics_parsed), 0);
        }

        return $object;
    }
}
