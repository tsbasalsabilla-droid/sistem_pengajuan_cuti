<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;


interface PluginCompleteInterface extends PluginInterface
{
    
    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue;
}
