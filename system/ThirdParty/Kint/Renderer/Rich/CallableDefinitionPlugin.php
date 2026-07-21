<?php

declare(strict_types=1);



namespace Kint\Renderer\Rich;

use Kint\Utils;
use Kint\Value\AbstractValue;
use Kint\Value\MethodValue;
use Kint\Value\Representation\CallableDefinitionRepresentation;
use Kint\Value\Representation\RepresentationInterface;

class CallableDefinitionPlugin extends AbstractPlugin implements TabPluginInterface
{
    public function renderTab(RepresentationInterface $r, AbstractValue $v): ?string
    {
        if (!$r instanceof CallableDefinitionRepresentation) {
            return null;
        }

        $docstring = [];

        if ($v instanceof MethodValue) {
            $c = $v->getContext();

            if ($c->inherited) {
                $docstring[] = 'Inherited from '.$this->renderer->escape($c->owner_class);
            }
        }

        $docstring[] = 'Defined in '.$this->renderer->escape(Utils::shortenPath($r->getFileName())).':'.$r->getLine();

        $docstring = '<small>'.\implode("\n", $docstring).'</small>';

        if (null !== ($trimmed = $r->getDocstringTrimmed())) {
            $docstring = $this->renderer->escape($trimmed)."\n\n".$docstring;
        }

        return '<pre>'.$docstring.'</pre>';
    }
}
