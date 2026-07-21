<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Utils;
use Kint\Value\AbstractValue;
use Kint\Value\Context\ArrayContext;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ClassOwnedContext;
use Kint\Value\Context\ContextInterface;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\Representation\ValueRepresentation;
use Kint\Value\SimpleXMLElementValue;
use SimpleXMLElement;

class SimpleXMLElementPlugin extends AbstractPlugin implements PluginBeginInterface
{
    
    public static bool $verbose = false;

    protected ClassMethodsPlugin $methods_plugin;

    public function __construct(Parser $parser)
    {
        parent::__construct($parser);

        $this->methods_plugin = new ClassMethodsPlugin($parser);
    }

    public function setParser(Parser $p): void
    {
        parent::setParser($p);

        $this->methods_plugin->setParser($p);
    }

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        
        
        return Parser::TRIGGER_BEGIN;
    }

    public function parseBegin(&$var, ContextInterface $c): ?AbstractValue
    {
        if (!$var instanceof SimpleXMLElement) {
            return null;
        }

        return $this->parseElement($var, $c);
    }

    protected function parseElement(SimpleXMLElement &$var, ContextInterface $c): SimpleXMLElementValue
    {
        $parser = $this->getParser();
        $pdepth = $parser->getDepthLimit();
        $cdepth = $c->getDepth();

        $depthlimit = $pdepth && $cdepth >= $pdepth;
        $has_children = self::hasChildElements($var);

        if ($depthlimit && $has_children) {
            $x = new SimpleXMLElementValue($c, $var, [], null);
            $x->flags |= AbstractValue::FLAG_DEPTH_LIMIT;

            return $x;
        }

        $children = $this->getChildren($c, $var);
        $attributes = $this->getAttributes($c, $var);
        $toString = (string) $var;
        $string_body = !$has_children && \strlen($toString);

        $x = new SimpleXMLElementValue($c, $var, $children, \strlen($toString) ? $toString : null);

        if (self::$verbose) {
            $x = $this->methods_plugin->parseComplete($var, $x, Parser::TRIGGER_SUCCESS);
        }

        if ($attributes) {
            $x->addRepresentation(new ContainerRepresentation('Attributes', $attributes), 0);
        }

        if ($string_body) {
            $base = new BaseContext('(string) '.$c->getName());
            $base->depth = $cdepth + 1;
            if (null !== ($ap = $c->getAccessPath())) {
                $base->access_path = '(string) '.$ap;
            }

            $toString = $parser->parse($toString, $base);

            $x->addRepresentation(new ValueRepresentation('toString', $toString, null, true), 0);
        }

        if ($children) {
            $x->addRepresentation(new ContainerRepresentation('Children', $children), 0);
        }

        return $x;
    }

    
    protected function getAttributes(ContextInterface $c, SimpleXMLElement $var): array
    {
        $parser = $this->getParser();
        $namespaces = \array_merge(['' => null], $var->getDocNamespaces());

        $cdepth = $c->getDepth();
        $ap = $c->getAccessPath();

        $contents = [];

        foreach ($namespaces as $nsAlias => $_) {
            if ((bool) $nsAttribs = $var->attributes($nsAlias, true)) {
                foreach ($nsAttribs as $name => $attrib) {
                    $obj = new ArrayContext($name);
                    $obj->depth = $cdepth + 1;

                    if (null !== $ap) {
                        $obj->access_path = '(string) '.$ap;
                        if ('' !== $nsAlias) {
                            $obj->access_path .= '->attributes('.\var_export($nsAlias, true).', true)';
                        }
                        $obj->access_path .= '['.\var_export($name, true).']';
                    }

                    if ('' !== $nsAlias) {
                        $obj->name = $nsAlias.':'.$obj->name;
                    }

                    $string = (string) $attrib;
                    $attribute = $parser->parse($string, $obj);

                    $contents[] = $attribute;
                }
            }
        }

        return $contents;
    }

    
    protected function getChildren(ContextInterface $c, SimpleXMLElement $var): array
    {
        $namespaces = \array_merge(['' => null], $var->getDocNamespaces());

        $cdepth = $c->getDepth();
        $ap = $c->getAccessPath();

        $contents = [];

        foreach ($namespaces as $nsAlias => $_) {
            $nsChildren = $var->children($nsAlias, true);
            if (!(bool) $nsChildren) {
                continue;
            }

            $nsap = [];

            foreach ($nsChildren as $name => $child) {
                $base = new ClassOwnedContext((string) $name, SimpleXMLElement::class);
                $base->depth = $cdepth + 1;

                if ('' !== $nsAlias) {
                    $base->name = $nsAlias.':'.$name;
                }

                if (null !== $ap) {
                    if ('' === $nsAlias) {
                        $base->access_path = $ap.'->';
                    } else {
                        $base->access_path = $ap.'->children('.\var_export($nsAlias, true).', true)->';
                    }

                    if (Utils::isValidPhpName((string) $name)) {
                        $base->access_path .= (string) $name;
                    } else {
                        $base->access_path .= '{'.\var_export((string) $name, true).'}';
                    }

                    if (isset($nsap[$base->access_path])) {
                        ++$nsap[$base->access_path];
                        $base->access_path .= '['.$nsap[$base->access_path].']';
                    } else {
                        $nsap[$base->access_path] = 0;
                    }
                }

                $v = $this->parseElement($child, $base);
                $v->flags |= AbstractValue::FLAG_GENERATED;
                $contents[] = $v;
            }
        }

        return $contents;
    }

    
    protected static function hasChildElements(SimpleXMLElement $var): bool
    {
        $namespaces = \array_merge(['' => null], $var->getDocNamespaces());

        foreach ($namespaces as $nsAlias => $_) {
            if ((array) $var->children($nsAlias, true)) {
                return true;
            }
        }

        return false;
    }
}
