<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\ContextInterface;

interface PluginBeginInterface extends PluginInterface
{
    
    public function parseBegin(&$var, ContextInterface $c): ?AbstractValue;
}
