<?php

declare(strict_types=1);



namespace Kint\Renderer\Rich;

use Kint\Renderer\RichRenderer;
use Kint\Utils;
use Kint\Value\AbstractValue;
use Kint\Value\Context\MethodContext;
use Kint\Value\MethodValue;

class CallablePlugin extends AbstractPlugin implements ValuePluginInterface
{
    protected static array $method_cache = [];

    public function renderValue(AbstractValue $v): ?string
    {
        if (!$v instanceof MethodValue) {
            return null;
        }

        $c = $v->getContext();

        if (!$c instanceof MethodContext) {
            return null;
        }

        if (!isset(self::$method_cache[$c->owner_class][$c->name])) {
            $children = $this->renderer->renderChildren($v);

            $header = '<var>'.$c->getModifiers();

            if ($v->getCallableBag()->return_reference) {
                $header .= ' &amp;';
            }

            $header .= '</var> ';

            $function = $this->renderer->escape($v->getDisplayName());

            if (null !== ($url = $v->getPhpDocUrl())) {
                $function = '<a href="'.$url.'" target=_blank>'.$function.'</a>';
            }

            $header .= '<dfn>'.$function.'</dfn>';

            if (null !== ($rt = $v->getCallableBag()->returntype)) {
                $header .= ': <var>';
                $header .= $this->renderer->escape($rt).'</var>';
            } elseif (null !== ($ds = $v->getCallableBag()->docstring)) {
                if (\preg_match('/@return\\s+(.*)\\r?\\n/m', $ds, $matches)) {
                    if (\trim($matches[1])) {
                        $header .= ': <var>'.$this->renderer->escape(\trim($matches[1])).'</var>';
                    }
                }
            }

            if (null !== ($s = $v->getDisplayValue())) {
                if (RichRenderer::$strlen_max) {
                    $s = Utils::truncateString($s, RichRenderer::$strlen_max);
                }
                $header .= ' '.$this->renderer->escape($s);
            }

            self::$method_cache[$c->owner_class][$c->name] = [
                'header' => $header,
                'children' => $children,
            ];
        }

        $children = self::$method_cache[$c->owner_class][$c->name]['children'];

        $header = $this->renderer->renderHeaderWrapper(
            $c,
            (bool) \strlen($children),
            self::$method_cache[$c->owner_class][$c->name]['header']
        );

        return '<dl>'.$header.$children.'</dl>';
    }
}
