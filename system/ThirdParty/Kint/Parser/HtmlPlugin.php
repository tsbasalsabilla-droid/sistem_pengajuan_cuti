<?php

declare(strict_types=1);



namespace Kint\Parser;

use Dom\HTMLDocument;
use DOMException;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\Representation\ValueRepresentation;

class HtmlPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public function getTypes(): array
    {
        return ['string'];
    }

    public function getTriggers(): int
    {
        if (!KINT_PHP84) {
            return Parser::TRIGGER_NONE; 
        }

        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if ('<!doctype html>' !== \strtolower((string) \substr($var, 0, 15))) {
            return $v;
        }

        try {
            $html = HTMLDocument::createFromString($var, LIBXML_NOERROR);
        } catch (DOMException $e) { 
            return $v; 
        }

        $c = $v->getContext();

        $base = new BaseContext('childNodes');
        $base->depth = $c->getDepth();

        if (null !== ($ap = $c->getAccessPath())) {
            $base->access_path = '\\Dom\\HTMLDocument::createFromString('.$ap.')->childNodes';
        }

        $out = $this->getParser()->parse($html->childNodes, $base);
        $iter = $out->getRepresentation('iterator');

        if ($out->flags & AbstractValue::FLAG_DEPTH_LIMIT) {
            $out->flags |= AbstractValue::FLAG_GENERATED;
            $v->addRepresentation(new ValueRepresentation('HTML', $out), 0);
        } elseif ($iter instanceof ContainerRepresentation) {
            $v->addRepresentation(new ContainerRepresentation('HTML', $iter->getContents()), 0);
        }

        return $v;
    }
}
