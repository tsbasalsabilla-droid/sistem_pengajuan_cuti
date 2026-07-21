<?php

declare(strict_types=1);



namespace Kint\Renderer\Rich;

use Kint\Utils;
use Kint\Value\AbstractValue;
use Kint\Value\Representation\MicrotimeRepresentation;
use Kint\Value\Representation\RepresentationInterface;

class MicrotimePlugin extends AbstractPlugin implements TabPluginInterface
{
    public function renderTab(RepresentationInterface $r, AbstractValue $v): ?string
    {
        if (!$r instanceof MicrotimeRepresentation || !($dt = $r->getDateTime())) {
            return null;
        }

        $out = $dt->format('Y-m-d H:i:s.u');
        if (null !== ($lap = $r->getLapTime())) {
            $out .= '<br><b>SINCE LAST CALL:</b> <span class="kint-microtime-lap">'.\round($lap, 4).'</span>s.';
        }
        if (null !== ($total = $r->getTotalTime())) {
            $out .= '<br><b>SINCE START:</b> '.\round($total, 4).'s.';
        }
        if (null !== ($avg = $r->getAverageTime())) {
            $out .= '<br><b>AVERAGE DURATION:</b> <span class="kint-microtime-avg">'.\round($avg, 4).'</span>s.';
        }

        $bytes = Utils::getHumanReadableBytes($r->getMemoryUsage());
        $out .= '<br><b>MEMORY USAGE:</b> '.$r->getMemoryUsage().' bytes ('.\round($bytes['value'], 3).' '.$bytes['unit'].')';
        $bytes = Utils::getHumanReadableBytes($r->getMemoryUsageReal());
        $out .= ' (real '.\round($bytes['value'], 3).' '.$bytes['unit'].')';

        $bytes = Utils::getHumanReadableBytes($r->getMemoryPeakUsage());
        $out .= '<br><b>PEAK MEMORY USAGE:</b> '.$r->getMemoryPeakUsage().' bytes ('.\round($bytes['value'], 3).' '.$bytes['unit'].')';
        $bytes = Utils::getHumanReadableBytes($r->getMemoryPeakUsageReal());
        $out .= ' (real '.\round($bytes['value'], 3).' '.$bytes['unit'].')';

        return '<pre data-kint-microtime-group="'.$r->getGroup().'">'.$out.'</pre>';
    }
}
