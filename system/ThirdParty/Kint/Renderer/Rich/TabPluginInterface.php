<?php

declare(strict_types=1);



namespace Kint\Renderer\Rich;

use Kint\Value\AbstractValue;
use Kint\Value\Representation\RepresentationInterface;

interface TabPluginInterface extends PluginInterface
{
    public function renderTab(RepresentationInterface $r, AbstractValue $v): ?string;
}
