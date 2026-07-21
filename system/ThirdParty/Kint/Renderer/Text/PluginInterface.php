<?php

declare(strict_types=1);



namespace Kint\Renderer\Text;

use Kint\Renderer\TextRenderer;
use Kint\Value\AbstractValue;

interface PluginInterface
{
    public function __construct(TextRenderer $r);

    public function render(AbstractValue $v): ?string;
}
