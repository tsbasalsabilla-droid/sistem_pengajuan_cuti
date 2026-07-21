<?php

declare(strict_types=1);



namespace Kint\Value\Context;

use __PHP_Incomplete_Class;

abstract class ClassDeclaredContext extends ClassOwnedContext
{
    public const ACCESS_PUBLIC = 1;
    public const ACCESS_PROTECTED = 2;
    public const ACCESS_PRIVATE = 3;

    
    public int $access;

    
    public ?string $proto_class = null;

    
    public function __construct(string $name, string $owner_class, int $access)
    {
        parent::__construct($name, $owner_class);
        $this->access = $access;
    }

    abstract public function getModifiers(): string;

    
    public function isAccessible(?string $scope): bool
    {
        if (__PHP_Incomplete_Class::class === $this->owner_class) {
            return false;
        }

        if (self::ACCESS_PUBLIC === $this->access) {
            return true;
        }

        if (null === $scope) {
            return false;
        }

        if (self::ACCESS_PRIVATE === $this->access) {
            return $scope === $this->owner_class;
        }

        if (KINT_PHP8412 && null !== $this->proto_class) {
            if (\is_a($scope, $this->proto_class, true)) {
                return true;
            }

            if (\is_a($this->proto_class, $scope, true)) {
                return true;
            }
        } else {
            if (\is_a($scope, $this->owner_class, true)) {
                return true;
            }

            if (\is_a($this->owner_class, $scope, true)) {
                return true;
            }
        }

        return false;
    }

    protected function getAccess(): string
    {
        switch ($this->access) {
            case self::ACCESS_PUBLIC:
                return 'public';
            case self::ACCESS_PROTECTED:
                return 'protected';
            case self::ACCESS_PRIVATE:
                return 'private';
        }
    }
}
