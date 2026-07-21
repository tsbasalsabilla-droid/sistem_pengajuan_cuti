<?php

declare(strict_types=1);



namespace Kint\Renderer\Text;

use Kint\Value\AbstractValue;

class LockPlugin extends AbstractPlugin
{
    public function render(AbstractValue $v): ?string
    {
        switch ($v->getHint()) {
            case 'blacklist':
                return $this->renderLockedHeader($v, 'BLACKLISTED');
            case 'recursion':
                return $this->renderLockedHeader($v, 'RECURSION');
            case 'depth_limit':
                return $this->renderLockedHeader($v, 'DEPTH LIMIT');
            case 'array_limit':
                return $this->renderLockedHeader($v, 'ARRAY LIMIT');
        }

        return null;
    }
}
