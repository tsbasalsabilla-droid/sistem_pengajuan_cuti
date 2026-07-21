<?php

declare(strict_types=1);



namespace Kint\Renderer\Text;

use Kint\Value\AbstractValue;
use Kint\Value\MethodValue;
use Kint\Value\Representation\SourceRepresentation;
use Kint\Value\TraceFrameValue;
use Kint\Value\TraceValue;

class TracePlugin extends AbstractPlugin
{
    public function render(AbstractValue $v): ?string
    {
        if (!$v instanceof TraceValue) {
            return null;
        }

        $c = $v->getContext();

        $out = '';

        if (0 === $c->getDepth()) {
            $out .= $this->renderer->colorTitle($this->renderer->renderTitle($v)).PHP_EOL;
        }

        $out .= $this->renderer->renderHeader($v).':'.PHP_EOL;

        $indent = \str_repeat(' ', ($c->getDepth() + 1) * $this->renderer->indent_width);

        $i = 1;
        foreach ($v->getContents() as $frame) {
            if (!$frame instanceof TraceFrameValue) {
                continue;
            }

            $framedesc = $indent.\str_pad($i.': ', 4, ' ');

            if (null !== ($file = $frame->getFile()) && null !== ($line = $frame->getLine())) {
                $framedesc .= $this->renderer->ideLink($file, $line).PHP_EOL;
            } else {
                $framedesc .= 'PHP internal call'.PHP_EOL;
            }

            if ($callable = $frame->getCallable()) {
                $framedesc .= $indent.'    ';

                if ($callable instanceof MethodValue) {
                    $framedesc .= $this->renderer->escape($callable->getContext()->owner_class.$callable->getContext()->getOperator());
                }

                $framedesc .= $this->renderer->escape($callable->getDisplayName());
            }

            $out .= $this->renderer->colorType($framedesc).PHP_EOL.PHP_EOL;

            $source = $frame->getRepresentation('source');

            if ($source instanceof SourceRepresentation) {
                $line_wanted = $source->getLine();
                $source = $source->getSourceLines();

                
                foreach ($source as $linenum => $line) {
                    if (\trim($line) || $linenum === $line_wanted) {
                        break;
                    }

                    unset($source[$linenum]);
                }

                foreach (\array_reverse($source, true) as $linenum => $line) {
                    if (\trim($line) || $linenum === $line_wanted) {
                        break;
                    }

                    unset($source[$linenum]);
                }

                foreach ($source as $lineno => $line) {
                    if ($lineno === $line_wanted) {
                        $out .= $indent.$this->renderer->colorValue($this->renderer->escape($line)).PHP_EOL;
                    } else {
                        $out .= $indent.$this->renderer->escape($line).PHP_EOL;
                    }
                }
            }

            ++$i;
        }

        return $out;
    }
}
