<?php

declare(strict_types=1);



namespace Kint\Renderer\Rich;

use Kint\Renderer\RichRenderer;
use Kint\Value\AbstractValue;
use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\PropertyContext;
use Kint\Value\InstanceValue;

abstract class AbstractPlugin implements PluginInterface
{
    protected RichRenderer $renderer;

    public function __construct(RichRenderer $r)
    {
        $this->renderer = $r;
    }

    
    public function renderLockedHeader(AbstractValue $v, string $content): string
    {
        $header = '<dt class="kint-parent kint-locked">';

        $c = $v->getContext();

        if (RichRenderer::$access_paths && $c->getDepth() > 0 && null !== ($ap = $c->getAccessPath())) {
            $header .= '<span class="kint-access-path-trigger" title="Show access path">&rlarr;</span>';
        }

        $header .= '<nav></nav>';

        if ($c instanceof ClassDeclaredContext) {
            $header .= '<var>'.$c->getModifiers().'</var> ';
        }

        $header .= '<dfn>'.$this->renderer->escape($v->getDisplayName()).'</dfn> ';

        if ($c instanceof PropertyContext && null !== ($s = $c->getHooks())) {
            $header .= '<var>'.$this->renderer->escape($s).'</var> ';
        }

        if (null !== ($s = $c->getOperator())) {
            $header .= $this->renderer->escape($s, 'ASCII').' ';
        }

        $s = $v->getDisplayType();

        if (RichRenderer::$escape_types) {
            $s = $this->renderer->escape($s);
        }

        if ($c->isRef()) {
            $s = '&amp;'.$s;
        }

        $header .= '<var>'.$s.'</var>';

        if ($v instanceof InstanceValue && $this->renderer->shouldRenderObjectIds()) {
            $header .= '#'.$v->getSplObjectId();
        }

        $header .= ' ';

        if (null !== ($s = $v->getDisplaySize())) {
            if (RichRenderer::$escape_types) {
                $s = $this->renderer->escape($s);
            }
            $header .= '('.$s.') ';
        }

        $header .= $content;

        if (!empty($ap)) {
            $header .= '<div class="access-path">'.$this->renderer->escape($ap).'</div>';
        }

        return $header.'</dt>';
    }
}
