<?php

declare(strict_types=1);



namespace CodeIgniter\API;

use CodeIgniter\HTTP\IncomingRequest;
use InvalidArgumentException;


abstract class BaseTransformer implements TransformerInterface
{
    
    private ?array $fields = null;

    
    private ?array $includes = null;

    protected mixed $resource = null;

    public function __construct(
        private ?IncomingRequest $request = null,
    ) {
        $this->request = $request ?? request();

        $fields       = $this->request->getGet('fields');
        $this->fields = is_string($fields)
            ? array_map(trim(...), explode(',', $fields))
            : $fields;

        $includes       = $this->request->getGet('include');
        $this->includes = is_string($includes)
            ? array_map(trim(...), explode(',', $includes))
            : $includes;
    }

    
    abstract public function toArray(mixed $resource): array;

    
    public function transform(array|object|null $resource = null): array
    {
        
        $this->resource = $resource;

        if ($resource === null) {
            $data = $this->toArray(null);
        } elseif (is_object($resource) && method_exists($resource, 'toArray')) {
            $data = $this->toArray($resource->toArray());
        } else {
            $data = $this->toArray((array) $resource);
        }

        $data = $this->limitFields($data);

        return $this->insertIncludes($data);
    }

    
    public function transformMany(array $resources): array
    {
        return array_map($this->transform(...), $resources);
    }

    
    protected function getAllowedFields(): ?array
    {
        return null;
    }

    
    protected function getAllowedIncludes(): ?array
    {
        return null;
    }

    
    private function limitFields(array $data): array
    {
        if ($this->fields === null || $this->fields === []) {
            return $data;
        }

        $allowedFields = $this->getAllowedFields();

        
        if ($allowedFields !== null) {
            $invalidFields = array_diff($this->fields, $allowedFields);

            if ($invalidFields !== []) {
                throw ApiException::forInvalidFields(implode(', ', $invalidFields));
            }
        }

        return array_intersect_key($data, array_flip($this->fields));
    }

    
    private function insertIncludes(array $data): array
    {
        if ($this->includes === null) {
            return $data;
        }

        $allowedIncludes = $this->getAllowedIncludes();

        if ($allowedIncludes === []) {
            return $data; 
        }

        
        if ($allowedIncludes !== null) {
            $invalidIncludes = array_diff($this->includes, $allowedIncludes);

            if ($invalidIncludes !== []) {
                throw ApiException::forInvalidIncludes(implode(', ', $invalidIncludes));
            }
        }

        foreach ($this->includes as $include) {
            $method = 'include' . ucfirst($include);
            if (method_exists($this, $method)) {
                $data[$include] = $this->{$method}();
            } else {
                throw ApiException::forMissingInclude($include);
            }
        }

        return $data;
    }
}
