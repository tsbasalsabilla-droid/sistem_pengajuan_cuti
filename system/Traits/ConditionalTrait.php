<?php

declare(strict_types=1);



namespace CodeIgniter\Traits;

trait ConditionalTrait
{
    
    public function when($condition, callable $callback, ?callable $defaultCallback = null): self
    {
        if ((bool) $condition) {
            $callback($this, $condition);
        } elseif ($defaultCallback !== null) {
            $defaultCallback($this);
        }

        return $this;
    }

    
    public function whenNot($condition, callable $callback, ?callable $defaultCallback = null): self
    {
        if (! (bool) $condition) {
            $callback($this, $condition);
        } elseif ($defaultCallback !== null) {
            $defaultCallback($this);
        }

        return $this;
    }
}
