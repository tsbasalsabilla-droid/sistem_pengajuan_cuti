<?php

declare(strict_types=1);



namespace Kint\Parser;

interface ConstructablePluginInterface extends PluginInterface
{
    public function __construct(Parser $p);
}
