<?php

declare(strict_types=1);



namespace CodeIgniter\Modules;


class Modules
{
    
    public $enabled = true;

    
    public $discoverInComposer = true;

    
    public $aliases = [];

    public function __construct()
    {
        
    }

    
    public function shouldDiscover(string $alias): bool
    {
        if (! $this->enabled) {
            return false;
        }

        return in_array(strtolower($alias), $this->aliases, true);
    }

    public static function __set_state(array $array)
    {
        $obj = new static();

        $properties = array_keys(get_object_vars($obj));

        foreach ($properties as $property) {
            $obj->{$property} = $array[$property];
        }

        return $obj;
    }
}
