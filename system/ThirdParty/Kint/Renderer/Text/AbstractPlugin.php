<?php

declare(strict_types=1);



namespace Kint\Renderer\Text;

use Kint\Renderer\TextRenderer;
use Kint\Value\AbstractValue;

abstract class AbstractPlugin implements PluginInterface
{
    protected TextRenderer $renderer;

    public function __construct(TextRenderer $r)
    {
        $this->renderer = $r;
    }

    public function renderLockedHeader(AbstractValue $v, ?string $content = null): string
    {
        $out = '';

        if (0 === $v->getContext()->getDepth()) {
            $out .= $this->renderer->colorTitle($this->renderer->renderTitle($v)).PHP_EOL;
        }

        $out .= $this->renderer->renderHeader($v);

        if (null !== $content) {
            $out .= ' '.$this->renderer->colorValue($content);
        }

        $out .= PHP_EOL;

        return $out;
    }
}
