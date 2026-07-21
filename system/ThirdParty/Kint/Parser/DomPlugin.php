<?php

declare(strict_types=1);



namespace Kint\Parser;

use Dom\Attr;
use Dom\CharacterData;
use Dom\Document;
use Dom\DocumentType;
use Dom\Element;
use Dom\HTMLElement;
use Dom\NamedNodeMap;
use Dom\Node;
use Dom\NodeList;
use DOMAttr;
use DOMCharacterData;
use DOMDocumentType;
use DOMElement;
use DOMNamedNodeMap;
use DOMNode;
use DOMNodeList;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\ContextInterface;
use Kint\Value\Context\PropertyContext;
use Kint\Value\DomNodeListValue;
use Kint\Value\DomNodeValue;
use Kint\Value\FixedWidthValue;
use Kint\Value\InstanceValue;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\StringValue;
use LogicException;
use ReflectionClass;

class DomPlugin extends AbstractPlugin implements PluginBeginInterface
{
    
    public const NODE_PROPS = [
        'nodeType' => true,
        'nodeName' => true,
        'baseURI' => true,
        'isConnected' => true,
        'ownerDocument' => true,
        'parentNode' => true,
        'parentElement' => true,
        'childNodes' => true,
        'firstChild' => true,
        'lastChild' => true,
        'previousSibling' => true,
        'nextSibling' => true,
        'nodeValue' => true,
        'textContent' => false,
    ];

    
    public const ELEMENT_PROPS = [
        'namespaceURI' => true,
        'prefix' => true,
        'localName' => true,
        'tagName' => true,
        'id' => false,
        'className' => false,
        'classList' => true,
        'attributes' => true,
        'firstElementChild' => true,
        'lastElementChild' => true,
        'childElementCount' => true,
        'previousElementSibling' => true,
        'nextElementSibling' => true,
        'innerHTML' => false,
        'outerHTML' => true,
        'substitutedNodeValue' => false,
        'children' => true,
    ];

    
    public const DOMNODE_PROPS = [
        'nodeName' => true,
        'nodeValue' => false,
        'nodeType' => true,
        'parentNode' => true,
        'parentElement' => true,
        'childNodes' => true,
        'firstChild' => true,
        'lastChild' => true,
        'previousSibling' => true,
        'nextSibling' => true,
        'attributes' => true,
        'isConnected' => true,
        'ownerDocument' => true,
        'namespaceURI' => true,
        'prefix' => false,
        'localName' => true,
        'baseURI' => true,
        'textContent' => false,
    ];

    
    public const DOMELEMENT_PROPS = [
        'tagName' => true,
        'className' => false,
        'id' => false,
        'schemaTypeInfo' => true,
        'firstElementChild' => true,
        'lastElementChild' => true,
        'childElementCount' => true,
        'previousElementSibling' => true,
        'nextElementSibling' => true,
    ];

    public const DOM_VERSIONS = [
        'parentElement' => KINT_PHP83,
        'isConnected' => KINT_PHP83,
        'className' => KINT_PHP83,
        'id' => KINT_PHP83,
        'firstElementChild' => KINT_PHP80,
        'lastElementChild' => KINT_PHP80,
        'childElementCount' => KINT_PHP80,
        'previousElementSibling' => KINT_PHP80,
        'nextElementSibling' => KINT_PHP80,
    ];

    
    public static array $blacklist = [
        'parentNode' => true,
        'firstChild' => true,
        'lastChild' => true,
        'previousSibling' => true,
        'nextSibling' => true,
        'firstElementChild' => true,
        'lastElementChild' => true,
        'parentElement' => true,
        'previousElementSibling' => true,
        'nextElementSibling' => true,
        'ownerDocument' => true,
    ];

    
    public static bool $verbose = false;

    
    protected static array $property_cache = [];

    protected ClassMethodsPlugin $methods_plugin;
    protected ClassStaticsPlugin $statics_plugin;

    public function __construct(Parser $parser)
    {
        parent::__construct($parser);

        $this->methods_plugin = new ClassMethodsPlugin($parser);
        $this->statics_plugin = new ClassStaticsPlugin($parser);
    }

    public function setParser(Parser $p): void
    {
        parent::setParser($p);

        $this->methods_plugin->setParser($p);
        $this->statics_plugin->setParser($p);
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
        
        
        if ($var instanceof Attr || $var instanceof CharacterData || $var instanceof DOMAttr || $var instanceof DOMCharacterData) {
            return $this->parseText($var, $c);
        }

        if ($var instanceof NamedNodeMap || $var instanceof NodeList || $var instanceof DOMNamedNodeMap || $var instanceof DOMNodeList) {
            return $this->parseList($var, $c);
        }

        if ($var instanceof Node || $var instanceof DOMNode) {
            return $this->parseNode($var, $c);
        }

        return null;
    }

    
    private function parseProperty(object $var, string $prop, ContextInterface $c): AbstractValue
    {
        
        if (@!isset($var->{$prop})) {
            return new FixedWidthValue($c, null);
        }

        $parser = $this->getParser();
        
        @$value = $var->{$prop};

        if (\is_scalar($value)) {
            return $parser->parse($value, $c);
        }

        if (isset(self::$blacklist[$prop])) {
            $b = new InstanceValue($c, \get_class($value), \spl_object_hash($value), \spl_object_id($value));
            $b->flags |= AbstractValue::FLAG_GENERATED | AbstractValue::FLAG_BLACKLIST;

            return $b;
        }

        
        if ($value instanceof Attr || $value instanceof CharacterData || $value instanceof DOMAttr || $value instanceof DOMCharacterData || $value instanceof NamedNodeMap || $value instanceof NodeList || $value instanceof DOMNamedNodeMap || $value instanceof DOMNodeList || $value instanceof Node || $value instanceof DOMNode) {
            $out = $this->parseBegin($value, $c);
        }

        if (!isset($out)) {
            
            $out = $parser->parse($value, $c); 
        }

        $out->flags |= AbstractValue::FLAG_GENERATED;

        return $out;
    }

    
    private function parseText(object $var, ContextInterface $c): AbstractValue
    {
        if ($c instanceof BaseContext && null !== $c->access_path) {
            $c->access_path .= '->nodeValue';
        }

        return $this->parseProperty($var, 'nodeValue', $c);
    }

    
    private function parseList(object $var, ContextInterface $c): InstanceValue
    {
        if ($var instanceof NodeList || $var instanceof DOMNodeList) {
            $v = new DomNodeListValue($c, $var);
        } else {
            $v = new InstanceValue($c, \get_class($var), \spl_object_hash($var), \spl_object_id($var));
        }

        $parser = $this->getParser();
        $pdepth = $parser->getDepthLimit();

        
        
        if (($var instanceof NodeList || $var instanceof DOMNodeList) && $pdepth && $c->getDepth() >= $pdepth) {
            $v->flags |= AbstractValue::FLAG_DEPTH_LIMIT;

            return $v;
        }

        if (self::$verbose) {
            $v = $this->methods_plugin->parseComplete($var, $v, Parser::TRIGGER_SUCCESS);
            $v = $this->statics_plugin->parseComplete($var, $v, Parser::TRIGGER_SUCCESS);
        }

        if (0 === $var->length) {
            $v->setChildren([]);

            return $v;
        }

        $cdepth = $c->getDepth();
        $ap = $c->getAccessPath();
        $contents = [];

        foreach ($var as $key => $item) {
            $base_obj = new BaseContext($item->nodeName);
            $base_obj->depth = $cdepth + 1;

            if ($var instanceof NamedNodeMap || $var instanceof DOMNamedNodeMap) {
                if (null !== $ap) {
                    $base_obj->access_path = $ap.'['.\var_export($item->nodeName, true).']';
                }
            } else { 
                if (null !== $ap) {
                    $base_obj->access_path = $ap.'['.\var_export($key, true).']';
                }
            }

            if ($item instanceof HTMLElement) {
                $base_obj->name = $item->localName;
            }

            $item = $parser->parse($item, $base_obj);
            $item->flags |= AbstractValue::FLAG_GENERATED;

            $contents[] = $item;
        }

        $v->setChildren($contents);

        if ($contents) {
            $v->addRepresentation(new ContainerRepresentation('Iterator', $contents), 0);
        }

        return $v;
    }

    
    private function parseNode(object $var, ContextInterface $c): DomNodeValue
    {
        $class = \get_class($var);
        $pdepth = $this->getParser()->getDepthLimit();

        if ($pdepth && $c->getDepth() >= $pdepth) {
            $v = new DomNodeValue($c, $var);
            $v->flags |= AbstractValue::FLAG_DEPTH_LIMIT;

            return $v;
        }

        if (($var instanceof DocumentType || $var instanceof DOMDocumentType) && $c instanceof BaseContext && $c->name === $var->nodeName) {
            $c->name = '!DOCTYPE '.$c->name;
        }

        $cdepth = $c->getDepth();
        $ap = $c->getAccessPath();

        $properties = [];
        $children = [];
        $attributes = [];

        foreach (self::getKnownProperties($var) as $prop => $readonly) {
            $prop_c = new PropertyContext($prop, $class, ClassDeclaredContext::ACCESS_PUBLIC);
            $prop_c->depth = $cdepth + 1;
            $prop_c->readonly = KINT_PHP81 && $readonly;

            if (null !== $ap) {
                $prop_c->access_path = $ap.'->'.$prop;
            }

            $properties[] = $prop_obj = $this->parseProperty($var, $prop, $prop_c);

            if ('childNodes' === $prop) {
                if (!$prop_obj instanceof DomNodeListValue) {
                    throw new LogicException('childNodes property parsed incorrectly'); 
                }
                $children = self::getChildren($prop_obj);
            } elseif ('attributes' === $prop) {
                $attributes = $prop_obj->getRepresentation('iterator');
                $attributes = $attributes instanceof ContainerRepresentation ? $attributes->getContents() : [];
            } elseif ('classList' === $prop) {
                if ($iter = $prop_obj->getRepresentation('iterator')) {
                    $prop_obj->removeRepresentation($iter);
                    $prop_obj->addRepresentation($iter, 0);
                }
            }
        }

        $v = new DomNodeValue($c, $var);
        
        $v->setChildren($properties);

        if ($children) {
            $v->addRepresentation(new ContainerRepresentation('Children', $children, null, true));
        }

        if ($attributes) {
            $v->addRepresentation(new ContainerRepresentation('Attributes', $attributes));
        }

        if (self::$verbose) {
            $v->addRepresentation(new ContainerRepresentation('Properties', $properties));

            $v = $this->methods_plugin->parseComplete($var, $v, Parser::TRIGGER_SUCCESS);
            $v = $this->statics_plugin->parseComplete($var, $v, Parser::TRIGGER_SUCCESS);
        }

        return $v;
    }

    
    public static function getKnownProperties(object $var): array
    {
        if (KINT_PHP81) {
            $r = new ReflectionClass($var);
            $classname = $r->getName();

            if (!isset(self::$property_cache[$classname])) {
                self::$property_cache[$classname] = [];

                foreach ($r->getProperties() as $prop) {
                    if ($prop->isStatic()) {
                        continue;
                    }

                    $declaring = $prop->getDeclaringClass()->getName();
                    $name = $prop->name;

                    if (\in_array($declaring, [Node::class, Element::class], true)) {
                        $readonly = self::NODE_PROPS[$name] ?? self::ELEMENT_PROPS[$name];
                    } elseif (\in_array($declaring, [DOMNode::class, DOMElement::class], true)) {
                        $readonly = self::DOMNODE_PROPS[$name] ?? self::DOMELEMENT_PROPS[$name];
                    } else {
                        continue;
                    }

                    self::$property_cache[$classname][$prop->name] = $readonly;
                }

                if ($var instanceof Document) {
                    self::$property_cache[$classname]['textContent'] = true;
                }

                if ($var instanceof Attr || $var instanceof CharacterData) {
                    self::$property_cache[$classname]['nodeValue'] = false;
                }
            }

            $known_properties = self::$property_cache[$classname];
        } else {
            $known_properties = self::DOMNODE_PROPS;
            if ($var instanceof DOMElement) {
                $known_properties += self::DOMELEMENT_PROPS;
            }

            foreach (self::DOM_VERSIONS as $key => $val) {
                if (false === $val) {
                    unset($known_properties[$key]); 
                }
            }
        }

        
        if (!self::$verbose) {
            $known_properties = \array_intersect_key($known_properties, [
                'nodeValue' => null,
                'childNodes' => null,
                'attributes' => null,
            ]);
        }

        return $known_properties;
    }

    
    private static function getChildren(DomNodeListValue $property): array
    {
        if (0 === $property->getLength()) {
            return [];
        }

        if ($property->flags & AbstractValue::FLAG_DEPTH_LIMIT) {
            return [$property];
        }

        $list_items = $property->getChildren();

        if (null === $list_items) {
            
            
            return []; 
        }

        $children = [];

        foreach ($list_items as $node) {
            
            if ($node instanceof StringValue && '#text' === $node->getContext()->getName()) {
                
                if (\ctype_space($node->getValue()) || '' === $node->getValue()) {
                    continue;
                }
            }

            $children[] = $node;
        }

        return $children;
    }
}
