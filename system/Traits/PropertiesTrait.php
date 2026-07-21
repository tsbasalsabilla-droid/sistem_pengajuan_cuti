<?php

declare(strict_types=1);



namespace CodeIgniter\Traits;

use ReflectionClass;
use ReflectionProperty;


trait PropertiesTrait
{
    
    final public function fill(array $params): self
    {
        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    
    final public function getPublicProperties(): array
    {
        $worker = new class () {
            public function getProperties(object $obj): array
            {
                return get_object_vars($obj);
            }
        };

        return $worker->getProperties($this);
    }

    
    final public function getNonPublicProperties(): array
    {
        $exclude    = ['view'];
        $properties = [];

        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED) as $property) {
            if ($property->isStatic() || in_array($property->getName(), $exclude, true)) {
                continue;
            }

            $properties[] = $property;
        }

        return $properties;
    }
}
