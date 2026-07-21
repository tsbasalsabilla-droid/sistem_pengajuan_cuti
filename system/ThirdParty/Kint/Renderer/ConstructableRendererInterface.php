<?php

declare(strict_types=1);



namespace Kint\Renderer;

interface ConstructableRendererInterface extends RendererInterface
{
    public function __construct();
}
