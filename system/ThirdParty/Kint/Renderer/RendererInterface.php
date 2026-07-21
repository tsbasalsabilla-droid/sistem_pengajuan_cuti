<?php

declare(strict_types=1);



namespace Kint\Renderer;

use Kint\Value\AbstractValue;

interface RendererInterface
{
    public function render(AbstractValue $v): string;

    public function shouldRenderObjectIds(): bool;

    public function setCallInfo(array $info): void;

    public function setStatics(array $statics): void;

    public function filterParserPlugins(array $plugins): array;

    public function preRender(): string;

    public function postRender(): string;
}
