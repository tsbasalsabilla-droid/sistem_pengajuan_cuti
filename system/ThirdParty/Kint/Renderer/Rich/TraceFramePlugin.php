<?php

declare(strict_types=1);



namespace Kint\Renderer\Rich;

use Kint\Value\AbstractValue;
use Kint\Value\MethodValue;
use Kint\Value\TraceFrameValue;

class TraceFramePlugin extends AbstractPlugin implements ValuePluginInterface
{
    public function renderValue(AbstractValue $v): ?string
    {
        if (!$v instanceof TraceFrameValue) {
            return null;
        }

        if (null !== ($file = $v->getFile()) && null !== ($line = $v->getLine())) {
            $header = '<var>'.$this->renderer->ideLink($file, $line).'</var> ';
        } else {
            $header = '<var>PHP internal call</var> ';
        }

        if ($callable = $v->getCallable()) {
            if ($callable instanceof MethodValue) {
                $function = $callable->getFullyQualifiedDisplayName();
            } else {
                $function = $callable->getDisplayName();
            }

            $function = $this->renderer->escape($function);

            if (null !== ($url = $callable->getPhpDocUrl())) {
                $function = '<a href="'.$url.'" target=_blank>'.$function.'</a>';
            }

            $header .= $function;
        }

        $children = $this->renderer->renderChildren($v);
        $header = $this->renderer->renderHeaderWrapper($v->getContext(), (bool) \strlen($children), $header);

        return '<dl>'.$header.$children.'</dl>';
    }
}
