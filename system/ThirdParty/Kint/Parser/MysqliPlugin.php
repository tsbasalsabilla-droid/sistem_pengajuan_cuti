<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\PropertyContext;
use Kint\Value\InstanceValue;
use Kint\Value\Representation\ContainerRepresentation;
use mysqli;
use Throwable;


class MysqliPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    
    public const ALWAYS_READABLE = [
        'client_version' => true,
        'connect_errno' => true,
        'connect_error' => true,
    ];

    
    public const EMPTY_READABLE = [
        'client_info' => true,
        'errno' => true,
        'error' => true,
    ];

    
    public const CONNECTED_READABLE = [
        'affected_rows' => true,
        'error_list' => true,
        'field_count' => true,
        'host_info' => true,
        'info' => true,
        'insert_id' => true,
        'server_info' => true,
        'server_version' => true,
        'sqlstate' => true,
        'protocol_version' => true,
        'thread_id' => true,
        'warning_count' => true,
    ];

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_COMPLETE;
    }

    
    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if (!$var instanceof mysqli || !$v instanceof InstanceValue) {
            return $v;
        }

        $props = $v->getRepresentation('properties');

        if (!$props instanceof ContainerRepresentation) {
            return $v;
        }

        
        try {
            $connected = \is_string(@$var->sqlstate);
        } catch (Throwable $t) {
            $connected = false;
        }

        try {
            $empty = !$connected && \is_string(@$var->client_info);
        } catch (Throwable $t) { 
            
            
            $empty = false; 
        }

        $parser = $this->getParser();

        $new_contents = [];

        foreach ($props->getContents() as $key => $obj) {
            $new_contents[$key] = $obj;

            $c = $obj->getContext();

            if (!$c instanceof PropertyContext) {
                continue;
            }

            if (isset(self::CONNECTED_READABLE[$c->getName()])) {
                $c->readonly = KINT_PHP81;
                if (!$connected) {
                    
                    continue; 
                }
            } elseif (isset(self::EMPTY_READABLE[$c->getName()])) {
                $c->readonly = KINT_PHP81;
                
                if (!$connected && !$empty) { 
                    continue; 
                }
            } elseif (!isset(self::ALWAYS_READABLE[$c->getName()])) {
                continue; 
            }

            $c->readonly = KINT_PHP81;

            
            if ((KINT_PHP81 ? 'uninitialized' : 'null') !== $obj->getType()) {
                continue;
            }

            $param = $var->{$c->getName()};

            
            if (!KINT_PHP81 && null === $param) {
                continue; 
            }

            $new_contents[$key] = $parser->parse($param, $c);
        }

        $new_contents = \array_values($new_contents);

        $v->setChildren($new_contents);

        if ($new_contents) {
            $v->replaceRepresentation(new ContainerRepresentation('Properties', $new_contents));
        }

        return $v;
    }
}
