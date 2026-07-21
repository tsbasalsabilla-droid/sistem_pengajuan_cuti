<?php

declare(strict_types=1);



namespace Kint\Parser;

use Dom\Node;
use Dom\XMLDocument;
use DOMDocument;
use DOMException;
use DOMNode;
use InvalidArgumentException;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ContextInterface;
use Kint\Value\Representation\ValueRepresentation;
use Throwable;

class XmlPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    
    public static string $parse_method = 'SimpleXML';

    public function getTypes(): array
    {
        return ['string'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if ('<?xml' !== \substr($var, 0, 5)) {
            return $v;
        }

        if (!\method_exists($this, 'xmlTo'.self::$parse_method)) {
            return $v;
        }

        $c = $v->getContext();

        $out = \call_user_func([$this, 'xmlTo'.self::$parse_method], $var, $c);

        if (null === $out) {
            return $v;
        }

        $out->flags |= AbstractValue::FLAG_GENERATED;

        $v->addRepresentation(new ValueRepresentation('XML', $out), 0);

        return $v;
    }

    
    protected function xmlToSimpleXML(string $var, ContextInterface $c): ?AbstractValue
    {
        $errors = \libxml_use_internal_errors(true);
        try {
            $xml = \simplexml_load_string($var);
            if (!(bool) $xml) {
                throw new InvalidArgumentException('Bad XML parse in XmlPlugin::xmlToSimpleXML');
            }
        } catch (Throwable $t) {
            return null;
        } finally {
            \libxml_use_internal_errors($errors);
            \libxml_clear_errors();
        }

        $base = new BaseContext($xml->getName());
        $base->depth = $c->getDepth() + 1;
        if (null !== ($ap = $c->getAccessPath())) {
            $base->access_path = 'simplexml_load_string('.$ap.')';
        }

        return $this->getParser()->parse($xml, $base);
    }

    
    protected function xmlToDOMDocument(string $var, ContextInterface $c): ?AbstractValue
    {
        try {
            $xml = new DOMDocument();
            $check = $xml->loadXML($var, LIBXML_NOWARNING | LIBXML_NOERROR);

            if (false === $check) {
                throw new InvalidArgumentException('Bad XML parse in XmlPlugin::xmlToDOMDocument');
            }
        } catch (Throwable $t) {
            return null;
        }

        $xml = $xml->firstChild;

        
        $base = new BaseContext($xml->nodeName);
        $base->depth = $c->getDepth() + 1;
        if (null !== ($ap = $c->getAccessPath())) {
            $base->access_path = '(function($s){$x = new \\DomDocument(); $x->loadXML($s); return $x;})('.$ap.')->firstChild';
        }

        return $this->getParser()->parse($xml, $base);
    }

    
    protected function xmlToXMLDocument(string $var, ContextInterface $c): ?AbstractValue
    {
        if (!KINT_PHP84) {
            return null; 
        }

        try {
            $xml = XMLDocument::createFromString($var, LIBXML_NOWARNING | LIBXML_NOERROR);
        } catch (DOMException $e) {
            return null;
        }

        $xml = $xml->firstChild;

        
        $base = new BaseContext($xml->nodeName);
        $base->depth = $c->getDepth() + 1;
        if (null !== ($ap = $c->getAccessPath())) {
            $base->access_path = '\\Dom\\XMLDocument::createFromString('.$ap.')->firstChild';
        }

        return $this->getParser()->parse($xml, $base);
    }
}
