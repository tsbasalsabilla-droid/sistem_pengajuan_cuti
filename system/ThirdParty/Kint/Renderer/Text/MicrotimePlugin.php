<?php

declare(strict_types=1);



namespace Kint\Renderer\Text;

use Kint\Renderer\PlainRenderer;
use Kint\Renderer\TextRenderer;
use Kint\Utils;
use Kint\Value\AbstractValue;
use Kint\Value\Representation\MicrotimeRepresentation;

class MicrotimePlugin extends AbstractPlugin
{
    protected bool $useJs = false;

    public function __construct(TextRenderer $r)
    {
        parent::__construct($r);

        if ($this->renderer instanceof PlainRenderer) {
            $this->useJs = true;
        }
    }

    public function render(AbstractValue $v): ?string
    {
        $r = $v->getRepresentation('microtime');

        if (!$r instanceof MicrotimeRepresentation || !($dt = $r->getDateTime())) {
            return null;
        }

        $c = $v->getContext();

        $out = '';

        if (0 === $c->getDepth()) {
            $out .= $this->renderer->colorTitle($this->renderer->renderTitle($v)).PHP_EOL;
        }

        $out .= $this->renderer->renderHeader($v);
        $out .= $this->renderer->renderChildren($v).PHP_EOL;

        $indent = \str_repeat(' ', ($c->getDepth() + 1) * $this->renderer->indent_width);

        if ($this->useJs) {
            $out .= '<span data-kint-microtime-group="'.$r->getGroup().'">';
        }

        $out .= $indent.$this->renderer->colorType('TIME:').' ';
        $out .= $this->renderer->colorValue($dt->format('Y-m-d H:i:s.u')).PHP_EOL;

        if (null !== ($lap = $r->getLapTime())) {
            $out .= $indent.$this->renderer->colorType('SINCE LAST CALL:').' ';

            $lap = \round($lap, 4);

            if ($this->useJs) {
                $lap = '<span class="kint-microtime-lap">'.$lap.'</span>';
            }

            $out .= $this->renderer->colorValue($lap.'s').'.'.PHP_EOL;
        }
        if (null !== ($total = $r->getTotalTime())) {
            $out .= $indent.$this->renderer->colorType('SINCE START:').' ';
            $out .= $this->renderer->colorValue(\round($total, 4).'s').'.'.PHP_EOL;
        }
        if (null !== ($avg = $r->getAverageTime())) {
            $out .= $indent.$this->renderer->colorType('AVERAGE DURATION:').' ';

            $avg = \round($avg, 4);

            if ($this->useJs) {
                $avg = '<span class="kint-microtime-avg">'.$avg.'</span>';
            }

            $out .= $this->renderer->colorValue($avg.'s').'.'.PHP_EOL;
        }

        $bytes = Utils::getHumanReadableBytes($r->getMemoryUsage());
        $mem = $r->getMemoryUsage().' bytes ('.\round($bytes['value'], 3).' '.$bytes['unit'].')';
        $bytes = Utils::getHumanReadableBytes($r->getMemoryUsageReal());
        $mem .= ' (real '.\round($bytes['value'], 3).' '.$bytes['unit'].')';

        $out .= $indent.$this->renderer->colorType('MEMORY USAGE:').' ';
        $out .= $this->renderer->colorValue($mem).'.'.PHP_EOL;

        $bytes = Utils::getHumanReadableBytes($r->getMemoryPeakUsage());
        $mem = $r->getMemoryPeakUsage().' bytes ('.\round($bytes['value'], 3).' '.$bytes['unit'].')';
        $bytes = Utils::getHumanReadableBytes($r->getMemoryPeakUsageReal());
        $mem .= ' (real '.\round($bytes['value'], 3).' '.$bytes['unit'].')';

        $out .= $indent.$this->renderer->colorType('PEAK MEMORY USAGE:').' ';
        $out .= $this->renderer->colorValue($mem).'.'.PHP_EOL;

        if ($this->useJs) {
            $out .= '</span>';
        }

        return $out;
    }
}
