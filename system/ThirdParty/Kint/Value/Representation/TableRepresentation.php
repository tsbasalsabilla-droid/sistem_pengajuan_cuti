<?php

declare(strict_types=1);



namespace Kint\Value\Representation;

use Kint\Value\AbstractValue;

class TableRepresentation extends ContainerRepresentation
{
    
    public function __construct(array $contents, ?string $name = null)
    {
        parent::__construct('Table', $contents, $name, false);
    }

    public function getHint(): string
    {
        return 'table';
    }
}
