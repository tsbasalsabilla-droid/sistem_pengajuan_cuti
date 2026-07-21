<?php

declare(strict_types=1);



namespace Kint\Parser;

use Dom\NamedNodeMap;
use Dom\NodeList;
use DOMNamedNodeMap;
use DOMNodeList;
use Kint\Value\AbstractValue;
use Kint\Value\ArrayValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\InstanceValue;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\Representation\ValueRepresentation;
use Kint\Value\UninitializedValue;
use mysqli_result;
use PDOStatement;
use SimpleXMLElement;
use SplFileObject;
use Throwable;
use Traversable;

class IteratorPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    
    public static array $blacklist = [
        NamedNodeMap::class,
        NodeList::class,
        DOMNamedNodeMap::class,
        DOMNodeList::class,
        mysqli_result::class,
        PDOStatement::class,
        SimpleXMLElement::class,
        SplFileObject::class,
    ];

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if (!$var instanceof Traversable || !$v instanceof InstanceValue || $v->getRepresentation('iterator')) {
            return $v;
        }

        $c = $v->getContext();

        foreach (self::$blacklist as $class) {
            if ($var instanceof $class) {
                $base = new BaseContext($class.' Iterator Contents');
                $base->depth = $c->getDepth() + 1;
                if (null !== ($ap = $c->getAccessPath())) {
                    $base->access_path = 'iterator_to_array('.$ap.', false)';
                }

                $b = new UninitializedValue($base);
                $b->flags |= AbstractValue::FLAG_BLACKLIST;

                $v->addRepresentation(new ValueRepresentation('Iterator', $b));

                return $v;
            }
        }

        try {
            $data = \iterator_to_array($var, false);
        } catch (Throwable $t) {
            return $v;
        }

        if (!\count($data)) {
            return $v;
        }

        $base = new BaseContext('Iterator Contents');
        $base->depth = $c->getDepth();
        if (null !== ($ap = $c->getAccessPath())) {
            $base->access_path = 'iterator_to_array('.$ap.', false)';
        }

        $iter_val = $this->getParser()->parse($data, $base);

        
        
        if ($iter_val instanceof ArrayValue && $iterator_items = $iter_val->getContents()) {
            $r = new ContainerRepresentation('Iterator', $iterator_items);
            $iterator_items = \array_values($iterator_items);
        } else {
            $r = new ValueRepresentation('Iterator', $iter_val);
            $iterator_items = [$iter_val];
        }

        if ((bool) $v->getChildren()) {
            $v->addRepresentation($r);
        } else {
            $v->setChildren($iterator_items);
            $v->addRepresentation($r, 0);
        }

        return $v;
    }
}
