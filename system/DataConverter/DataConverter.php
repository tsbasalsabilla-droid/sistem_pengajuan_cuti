<?php

declare(strict_types=1);



namespace CodeIgniter\DataConverter;

use Closure;
use CodeIgniter\DataCaster\DataCaster;
use CodeIgniter\Entity\Entity;


final  class DataConverter
{
    
    private DataCaster $dataCaster;

    
    public function __construct(
        
        private array $types,
        array $castHandlers = [],
        
        private ?object $helper = null,
        
        private Closure|string|null $reconstructor = 'reconstruct',
        
        private Closure|string|null $extractor = null,
    ) {
        $this->dataCaster = new DataCaster($castHandlers, $types, $this->helper);
    }

    
    public function fromDataSource(array $data): array
    {
        foreach (array_keys($this->types) as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->dataCaster->castAs($data[$field], $field, 'get');
            }
        }

        return $data;
    }

    
    public function toDataSource(array $phpData): array
    {
        foreach (array_keys($this->types) as $field) {
            if (array_key_exists($field, $phpData)) {
                $phpData[$field] = $this->dataCaster->castAs($phpData[$field], $field, 'set');
            }
        }

        return $phpData;
    }

    
    public function reconstruct(string $classname, array $row): object
    {
        $phpData = $this->fromDataSource($row);

        
        if (is_string($this->reconstructor) && method_exists($classname, $this->reconstructor)) {
            $method = $this->reconstructor;

            return $classname::$method($phpData);
        }

        
        if ($this->reconstructor instanceof Closure) {
            $closure = $this->reconstructor;

            return $closure($phpData);
        }

        $classObj = new $classname();

        if ($classObj instanceof Entity) {
            $classObj->injectRawData($phpData);
            $classObj->syncOriginal();

            return $classObj;
        }

        $classSet = Closure::bind(function ($key, $value): void {
            $this->{$key} = $value;
        }, $classObj, $classname);

        foreach ($phpData as $key => $value) {
            $classSet($key, $value);
        }

        return $classObj;
    }

    
    public function extract(object $object, bool $onlyChanged = false, bool $recursive = false): array
    {
        
        if (is_string($this->extractor) && method_exists($object, $this->extractor)) {
            $method = $this->extractor;
            $row    = $object->{$method}($onlyChanged, $recursive);

            return $this->toDataSource($row);
        }

        
        if ($this->extractor instanceof Closure) {
            $closure = $this->extractor;
            $row     = $closure($object, $onlyChanged, $recursive);

            return $this->toDataSource($row);
        }

        if ($object instanceof Entity) {
            $row = $object->toRawArray($onlyChanged, $recursive);

            return $this->toDataSource($row);
        }

        $array = (array) $object;

        $row = [];

        foreach ($array as $key => $value) {
            $key = preg_replace('/\000.*\000/', '', $key);

            $row[$key] = $value;
        }

        return $this->toDataSource($row);
    }
}
