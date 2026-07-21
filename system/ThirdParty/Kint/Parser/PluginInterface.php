<?php

declare(strict_types=1);



namespace Kint\Parser;


interface PluginInterface
{
    public function setParser(Parser $p): void;

    public function getTypes(): array;

    
    public function getTriggers(): int;
}
