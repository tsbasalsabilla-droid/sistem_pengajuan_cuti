<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\ContextInterface;
use Kint\Value\InstanceValue;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class BlacklistPlugin extends AbstractPlugin implements PluginBeginInterface
{
    
    public static array $blacklist = [];

    
    public static array $shallow_blacklist = [
        ContainerInterface::class,
        EventDispatcherInterface::class,
    ];

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_BEGIN;
    }

    public function parseBegin(&$var, ContextInterface $c): ?AbstractValue
    {
        foreach (self::$blacklist as $class) {
            if ($var instanceof $class) {
                return $this->blacklistValue($var, $c);
            }
        }

        if ($c->getDepth() <= 0) {
            return null;
        }

        foreach (self::$shallow_blacklist as $class) {
            if ($var instanceof $class) {
                return $this->blacklistValue($var, $c);
            }
        }

        return null;
    }

    
    protected function blacklistValue(&$var, ContextInterface $c): InstanceValue
    {
        $object = new InstanceValue($c, \get_class($var), \spl_object_hash($var), \spl_object_id($var));
        $object->flags |= AbstractValue::FLAG_BLACKLIST;

        return $object;
    }
}
