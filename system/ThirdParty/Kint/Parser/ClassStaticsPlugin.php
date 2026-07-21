<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\ClassConstContext;
use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\StaticPropertyContext;
use Kint\Value\InstanceValue;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\UninitializedValue;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionProperty;
use UnitEnum;

class ClassStaticsPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    
    private array $cache = [];

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
        if (!$v instanceof InstanceValue) {
            return $v;
        }

        $deep = 0 === $this->getParser()->getDepthLimit();

        $r = new ReflectionClass($v->getClassName());

        if ($statics = $this->getStatics($r, $v->getContext()->getDepth() + 1)) {
            $v->addRepresentation(new ContainerRepresentation('Static properties', \array_values($statics), 'statics'));
        }

        if ($consts = $this->getCachedConstants($r, $deep)) {
            $v->addRepresentation(new ContainerRepresentation('Class constants', \array_values($consts), 'constants'));
        }

        return $v;
    }

    
    private function getStatics(ReflectionClass $r, int $depth): array
    {
        $cdepth = $depth ?: 1;
        $class = $r->getName();
        $parent = $r->getParentClass();

        $parent_statics = $parent ? $this->getStatics($parent, $depth) : [];
        $statics = [];

        foreach ($r->getProperties(ReflectionProperty::IS_STATIC) as $pr) {
            $canon_name = \strtolower($pr->getDeclaringClass()->name.'::'.$pr->name);

            if ($pr->getDeclaringClass()->name === $class) {
                $statics[$canon_name] = $this->buildStaticValue($pr, $cdepth);
            } elseif (isset($parent_statics[$canon_name])) {
                $statics[$canon_name] = $parent_statics[$canon_name];
                unset($parent_statics[$canon_name]);
            } else {
                
                $statics[$canon_name] = $this->buildStaticValue($pr, $cdepth); 
            }
        }

        foreach ($parent_statics as $canon_name => $value) {
            $statics[$canon_name] = $value;
        }

        return $statics;
    }

    private function buildStaticValue(ReflectionProperty $pr, int $depth): AbstractValue
    {
        $context = new StaticPropertyContext(
            $pr->name,
            $pr->getDeclaringClass()->name,
            ClassDeclaredContext::ACCESS_PUBLIC
        );
        $context->depth = $depth;
        $context->final = KINT_PHP84 && $pr->isFinal();

        if ($pr->isProtected()) {
            $context->access = ClassDeclaredContext::ACCESS_PROTECTED;
        } elseif ($pr->isPrivate()) {
            $context->access = ClassDeclaredContext::ACCESS_PRIVATE;
        }

        $parser = $this->getParser();

        if ($context->isAccessible($parser->getCallerClass())) {
            $context->access_path = '\\'.$context->owner_class.'::$'.$context->name;
        }

        if (KINT_PHP81 === false) {
            $pr->setAccessible(true);
        }

        
        if (!$pr->isInitialized()) {
            $context->access_path = null;

            return new UninitializedValue($context);
        }

        $val = $pr->getValue();

        $out = $this->getParser()->parse($val, $context);
        $context->access_path = null;

        return $out;
    }

    
    private function getCachedConstants(ReflectionClass $r, bool $deep): array
    {
        $parser = $this->getParser();
        $cdepth = $parser->getDepthLimit() ?: 1;
        $deepkey = (int) $deep;
        $class = $r->getName();

        
        
        if (!isset($this->cache[$class][$deepkey])) {
            $consts = [];

            $parent_consts = [];
            if ($parent = $r->getParentClass()) {
                $parent_consts = $this->getCachedConstants($parent, $deep);
            }
            foreach ($r->getConstants() as $name => $val) {
                $cr = new ReflectionClassConstant($class, $name);

                
                if ($cr->class === $class && \is_a($class, UnitEnum::class, true)) {
                    continue;
                }

                $canon_name = \strtolower($cr->getDeclaringClass()->name.'::'.$name);

                if ($cr->getDeclaringClass()->name === $class) {
                    $context = $this->buildConstContext($cr);
                    $context->depth = $cdepth;

                    $consts[$canon_name] = $parser->parse($val, $context);
                    $context->access_path = null;
                } elseif (isset($parent_consts[$canon_name])) {
                    $consts[$canon_name] = $parent_consts[$canon_name];
                } else {
                    $context = $this->buildConstContext($cr);
                    $context->depth = $cdepth;

                    $consts[$canon_name] = $parser->parse($val, $context);
                    $context->access_path = null;
                }

                unset($parent_consts[$canon_name]);
            }

            $this->cache[$class][$deepkey] = $consts + $parent_consts;
        }

        return $this->cache[$class][$deepkey];
    }

    private function buildConstContext(ReflectionClassConstant $cr): ClassConstContext
    {
        $context = new ClassConstContext(
            $cr->name,
            $cr->getDeclaringClass()->name,
            ClassDeclaredContext::ACCESS_PUBLIC
        );
        $context->final = KINT_PHP81 && $cr->isFinal();

        if ($cr->isProtected()) {
            $context->access = ClassDeclaredContext::ACCESS_PROTECTED;
        } elseif ($cr->isPrivate()) {
            $context->access = ClassDeclaredContext::ACCESS_PRIVATE;
        } else {
            $context->access_path = '\\'.$context->owner_class.'::'.$context->name;
        }

        return $context;
    }
}
