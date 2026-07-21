<?php

declare(strict_types=1);



namespace Kint\Value\Context;

class PropertyContext extends DoubleAccessMemberContext
{
    public const HOOK_NONE = 0;
    public const HOOK_GET = 1 << 0;
    public const HOOK_GET_REF = 1 << 1;
    public const HOOK_SET = 1 << 2;
    public const HOOK_SET_TYPE = 1 << 3;

    public bool $readonly = false;
    
    public int $hooks = self::HOOK_NONE;
    public ?string $hook_set_type = null;

    public function getModifiers(): string
    {
        $out = $this->getAccess();

        if ($this->readonly) {
            $out .= ' readonly';
        }

        return $out;
    }

    public function getHooks(): ?string
    {
        if (self::HOOK_NONE === $this->hooks) {
            return null;
        }

        $out = '{ ';
        if ($this->hooks & self::HOOK_GET) {
            if ($this->hooks & self::HOOK_GET_REF) {
                $out .= '&';
            }
            $out .= 'get; ';
        }
        if ($this->hooks & self::HOOK_SET) {
            if ($this->hooks & self::HOOK_SET_TYPE && '' !== ($this->hook_set_type ?? '')) {
                $out .= 'set('.$this->hook_set_type.'); ';
            } else {
                $out .= 'set; ';
            }
        }
        $out .= '}';

        return $out;
    }
}
