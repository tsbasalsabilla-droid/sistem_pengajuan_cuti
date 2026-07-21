<?php

declare(strict_types=1);



namespace Kint\Renderer\Text;

use Kint\Value\AbstractValue;
use Kint\Value\Representation\SplFileInfoRepresentation;

class SplFileInfoPlugin extends AbstractPlugin
{
    public function render(AbstractValue $v): ?string
    {
        $r = $v->getRepresentation('splfileinfo');

        if (!$r instanceof SplFileInfoRepresentation) {
            return null;
        }

        $out = '';

        $c = $v->getContext();

        if (0 === $c->getDepth()) {
            $out .= $this->renderer->colorTitle($this->renderer->renderTitle($v)).PHP_EOL;
        }

        $out .= $this->renderer->renderHeader($v);
        if (null !== $v->getDisplayValue()) {
            $out .= ' =>';
        }
        $out .= ' '.$this->renderer->colorValue($this->renderer->escape($r->getValue())).PHP_EOL;

        return $out;
    }
}
