<?php

declare(strict_types=1);



namespace Kint\Value\Context;

abstract class DoubleAccessMemberContext extends ClassDeclaredContext
{
    
    public ?int $access_set = null;

    protected function getAccess(): string
    {
        switch ($this->access) {
            case self::ACCESS_PUBLIC:
                if (self::ACCESS_PRIVATE === $this->access_set) {
                    return 'private(set)';
                }
                if (self::ACCESS_PROTECTED === $this->access_set) {
                    return 'protected(set)';
                }

                return 'public';

            case self::ACCESS_PROTECTED:
                if (self::ACCESS_PRIVATE === $this->access_set) {
                    return 'protected private(set)';
                }

                return 'protected';

            case self::ACCESS_PRIVATE:
                return 'private';
        }
    }
}
