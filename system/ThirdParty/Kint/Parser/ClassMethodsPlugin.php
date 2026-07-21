<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\MethodContext;
use Kint\Value\DeclaredCallableBag;
use Kint\Value\InstanceValue;
use Kint\Value\MethodValue;
use Kint\Value\Representation\ContainerRepresentation;
use ReflectionClass;
use ReflectionMethod;

class ClassMethodsPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public static bool $show_access_path = true;

    
    public static bool $show_constructor_path = false;

    
    private array $instance_cache = [];

    
    private array $static_cache = [];

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

        $class = $v->getClassName();
        $scope = $this->getParser()->getCallerClass();

        if ($contents = $this->getCachedMethods($class)) {
            if (self::$show_access_path) {
                if (null !== $v->getContext()->getAccessPath()) {
                    
                    foreach ($contents as $key => $val) {
                        if ($val->getContext()->isAccessible($scope)) {
                            $val = clone $val;
                            $val->getContext()->setAccessPathFromParent($v);
                            $contents[$key] = $val;
                        }
                    }
                } elseif (self::$show_constructor_path && isset($contents['__construct'])) {
                    
                    
                    
                    
                    $val = $contents['__construct'];
                    if ($val->getContext()->isAccessible($scope)) {
                        $val = clone $val;
                        $val->getContext()->setAccessPathFromParent($v);
                        $contents['__construct'] = $val;
                    }
                }
            }

            $v->addRepresentation(new ContainerRepresentation('Methods', $contents));
        }

        if ($contents = $this->getCachedStaticMethods($class)) {
            $v->addRepresentation(new ContainerRepresentation('Static methods', $contents));
        }

        return $v;
    }

    
    private function getCachedMethods(string $class): array
    {
        if (!isset($this->instance_cache[$class])) {
            $methods = [];

            $r = new ReflectionClass($class);

            $parent_methods = [];
            if ($parent = \get_parent_class($class)) {
                $parent_methods = $this->getCachedMethods($parent);
            }

            foreach ($r->getMethods() as $mr) {
                if ($mr->isStatic()) {
                    continue;
                }

                $canon_name = \strtolower($mr->name);
                if ($mr->isPrivate() && '__construct' !== $canon_name) {
                    $canon_name = \strtolower($mr->getDeclaringClass()->name).'::'.$canon_name;
                }

                if ($mr->getDeclaringClass()->name === $class) {
                    $method = new MethodValue(new MethodContext($mr), new DeclaredCallableBag($mr));
                    $methods[$canon_name] = $method;
                    unset($parent_methods[$canon_name]);
                } elseif (isset($parent_methods[$canon_name])) {
                    $method = $parent_methods[$canon_name];
                    unset($parent_methods[$canon_name]);

                    if (!$method->getContext()->inherited) {
                        $method = clone $method;
                        $method->getContext()->inherited = true;
                    }

                    $methods[$canon_name] = $method;
                } elseif ($mr->getDeclaringClass()->isInterface()) {
                    $c = new MethodContext($mr);
                    $c->inherited = true;
                    $methods[$canon_name] = new MethodValue($c, new DeclaredCallableBag($mr));
                }
            }

            foreach ($parent_methods as $name => $method) {
                if (!$method->getContext()->inherited) {
                    $method = clone $method;
                    $method->getContext()->inherited = true;
                }

                if ('__construct' === $name) {
                    $methods['__construct'] = $method;
                } else {
                    $methods[] = $method;
                }
            }

            $this->instance_cache[$class] = $methods;
        }

        return $this->instance_cache[$class];
    }

    
    private function getCachedStaticMethods(string $class): array
    {
        if (!isset($this->static_cache[$class])) {
            $methods = [];

            $r = new ReflectionClass($class);

            $parent_methods = [];
            if ($parent = \get_parent_class($class)) {
                $parent_methods = $this->getCachedStaticMethods($parent);
            }

            foreach ($r->getMethods(ReflectionMethod::IS_STATIC) as $mr) {
                $canon_name = \strtolower($mr->getDeclaringClass()->name.'::'.$mr->name);

                if ($mr->getDeclaringClass()->name === $class) {
                    $method = new MethodValue(new MethodContext($mr), new DeclaredCallableBag($mr));
                    $methods[$canon_name] = $method;
                } elseif (isset($parent_methods[$canon_name])) {
                    $methods[$canon_name] = $parent_methods[$canon_name];
                } elseif ($mr->getDeclaringClass()->isInterface()) {
                    $c = new MethodContext($mr);
                    $c->inherited = true;
                    $methods[$canon_name] = new MethodValue($c, new DeclaredCallableBag($mr));
                }

                unset($parent_methods[$canon_name]);
            }

            $this->static_cache[$class] = $methods + $parent_methods;
        }

        return $this->static_cache[$class];
    }
}
