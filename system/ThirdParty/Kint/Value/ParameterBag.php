<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Utils;
use ReflectionParameter;

final class ParameterBag
{
    
    public string $name;
    
    public int $position;
    
    public bool $ref;
    
    public ?string $type_hint;
    
    public ?string $default;

    public function __construct(ReflectionParameter $param)
    {
        $this->name = $param->getName();
        $this->position = $param->getPosition();
        $this->ref = $param->isPassedByReference();

        $this->type_hint = ($type = $param->getType()) ? Utils::getTypeString($type) : null;

        if ($param->isDefaultValueAvailable()) {
            $default = $param->getDefaultValue();
            switch (\gettype($default)) {
                case 'NULL':
                    $this->default = 'null';
                    break;
                case 'boolean':
                    $this->default = $default ? 'true' : 'false';
                    break;
                case 'array':
                    $this->default = \count($default) ? 'array(...)' : 'array()';
                    break;
                case 'double':
                case 'integer':
                case 'string':
                    $this->default = \var_export($default, true);
                    break;
                case 'object':
                    $this->default = 'object('.\get_class($default).')';
                    break;
                default:
                    $this->default = \gettype($default);
                    break;
            }
        } else {
            $this->default = null;
        }
    }

    public function __toString()
    {
        $type = $this->type_hint;
        if (null !== $type) {
            $type .= ' ';
        }

        $default = $this->default;
        if (null !== $default) {
            $default = ' = '.$default;
        }

        $ref = $this->ref ? '&' : '';

        return $type.$ref.'$'.$this->name.$default;
    }
}
