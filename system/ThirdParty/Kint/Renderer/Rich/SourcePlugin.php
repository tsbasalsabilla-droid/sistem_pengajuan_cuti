<?php

declare(strict_types=1);



namespace Kint\Renderer\Rich;

use Kint\Value\AbstractValue;
use Kint\Value\Representation\RepresentationInterface;
use Kint\Value\Representation\SourceRepresentation;

class SourcePlugin extends AbstractPlugin implements TabPluginInterface
{
    public function renderTab(RepresentationInterface $r, AbstractValue $v): ?string
    {
        if (!$r instanceof SourceRepresentation) {
            return null;
        }

        $source = $r->getSourceLines();

        
        foreach ($source as $linenum => $line) {
            if (\strlen(\trim($line)) || $linenum === $r->getLine()) {
                break;
            }

            unset($source[$linenum]);
        }

        foreach (\array_reverse($source, true) as $linenum => $line) {
            if (\strlen(\trim($line)) || $linenum === $r->getLine()) {
                break;
            }

            unset($source[$linenum]);
        }

        $output = '';

        foreach ($source as $linenum => $line) {
            if ($linenum === $r->getLine()) {
                $output .= '<div class="kint-highlight">'.$this->renderer->escape($line)."\n".'</div>';
            } else {
                $output .= '<div>'.$this->renderer->escape($line)."\n".'</div>';
            }
        }

        if ($output) {
            $data = '';
            if ($r->showFileName()) {
                $data = ' data-kint-filename="'.$this->renderer->escape($r->getFileName()).'"';
            }

            return '<div><pre class="kint-source"'.$data.' style="counter-reset: kint-l '.((int) \array_key_first($source) - 1).';">'.$output.'</pre></div><div></div>';
        }

        return null;
    }
}
