<?php

declare(strict_types=1);



namespace Kint\Renderer\Rich;

use Kint\Value\AbstractValue;
use Kint\Value\Representation\ProfileRepresentation;
use Kint\Value\Representation\RepresentationInterface;

class ProfilePlugin extends AbstractPlugin implements TabPluginInterface
{
    public function renderTab(RepresentationInterface $r, AbstractValue $v): ?string
    {
        if (!$r instanceof ProfileRepresentation) {
            return null;
        }

        $out = '<pre>';

        $out .= 'Complexity: '.$r->complexity.PHP_EOL;
        if (isset($r->instance_counts)) {
            $out .= 'Instance repetitions: '.\var_export($r->instance_counts, true).PHP_EOL;
        }
        if (isset($r->instance_complexity)) {
            $out .= 'Instance complexity: '.\var_export($r->instance_complexity, true).PHP_EOL;
        }

        $out .= '</pre>';

        return $out;
    }
}
