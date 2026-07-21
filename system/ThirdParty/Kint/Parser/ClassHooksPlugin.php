<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\MethodContext;
use Kint\Value\Context\PropertyContext;
use Kint\Value\DeclaredCallableBag;
use Kint\Value\InstanceValue;
use Kint\Value\MethodValue;
use Kint\Value\Representation\ContainerRepresentation;
use ReflectionProperty;

class ClassHooksPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public static bool $verbose = false;

    
    private array $cache = [];
    
    private array $cache_verbose = [];

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        if (!KINT_PHP84) {
            return Parser::TRIGGER_NONE; 
        }

        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if (!$v instanceof InstanceValue) {
            return $v;
        }

        $props = $v->getRepresentation('properties');

        if (!$props instanceof ContainerRepresentation) {
            return $v;
        }

        foreach ($props->getContents() as $prop) {
            $c = $prop->getContext();

            if (!$c instanceof PropertyContext || PropertyContext::HOOK_NONE === $c->hooks) {
                continue;
            }

            $cname = $c->getName();
            $cowner = $c->owner_class;

            if (!isset($this->cache_verbose[$cowner][$cname])) {
                $ref = new ReflectionProperty($cowner, $cname);
                $hooks = $ref->getHooks();

                foreach ($hooks as $hook) {
                    if (!self::$verbose && false === $hook->getDocComment()) {
                        continue;
                    }

                    $m = new MethodValue(
                        new MethodContext($hook),
                        new DeclaredCallableBag($hook)
                    );

                    $this->cache_verbose[$cowner][$cname][] = $m;

                    if (false !== $hook->getDocComment()) {
                        $this->cache[$cowner][$cname][] = $m;
                    }
                }

                $this->cache[$cowner][$cname] ??= [];

                if (self::$verbose) {
                    $this->cache_verbose[$cowner][$cname] ??= [];
                }
            }

            $cache = self::$verbose ? $this->cache_verbose : $this->cache;
            $cache = $cache[$cowner][$cname] ?? [];

            if (\count($cache)) {
                $prop->addRepresentation(new ContainerRepresentation('Hooks', $cache, 'propertyhooks'));
            }
        }

        return $v;
    }
}
