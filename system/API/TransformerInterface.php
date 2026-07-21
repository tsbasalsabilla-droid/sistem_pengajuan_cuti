<?php

declare(strict_types=1);



namespace CodeIgniter\API;


interface TransformerInterface
{
    
    public function toArray(mixed $resource): array;

    
    public function transform(array|object|null $resource): array;

    
    public function transformMany(array $resources): array;
}
