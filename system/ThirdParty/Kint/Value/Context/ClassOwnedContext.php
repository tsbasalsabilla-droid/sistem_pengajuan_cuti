<?php

declare(strict_types=1);



namespace Kint\Value\Context;

use __PHP_Incomplete_Class;

class ClassOwnedContext extends BaseContext
{
    
    public string $owner_class;

    
    public function __construct(string $name, string $owner_class)
    {
        parent::__construct($name);
        $this->owner_class = $owner_class;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }

    public function getOperator(): string
    {
        return '->';
    }

    
    public function isAccessible(?string $scope): bool
    {
        return __PHP_Incomplete_Class::class !== $this->owner_class;
    }
}
