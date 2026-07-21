<?php

declare(strict_types=1);



namespace Kint\Renderer\Rich;

use Kint\Value\AbstractValue;

interface ValuePluginInterface extends PluginInterface
{
    public function renderValue(AbstractValue $v): ?string;
}
