<?php

declare(strict_types=1);



namespace Kint\Renderer\Rich;

use Kint\Value\AbstractValue;

class LockPlugin extends AbstractPlugin implements ValuePluginInterface
{
    public function renderValue(AbstractValue $v): ?string
    {
        switch ($v->getHint()) {
            case 'blacklist':
                return '<dl>'.$this->renderLockedHeader($v, '<var>Blacklisted</var>').'</dl>';
            case 'recursion':
                return '<dl>'.$this->renderLockedHeader($v, '<var>Recursion</var>').'</dl>';
            case 'depth_limit':
                return '<dl>'.$this->renderLockedHeader($v, '<var>Depth Limit</var>').'</dl>';
            case 'array_limit':
                return '<dl>'.$this->renderLockedHeader($v, '<var>Array Limit</var>').'</dl>';
        }

        return null;
    }
}
