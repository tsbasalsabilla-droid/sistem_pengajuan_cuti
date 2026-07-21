<?php

declare(strict_types=1);



namespace Kint\Parser;

use JsonException;
use Kint\Value\AbstractValue;
use Kint\Value\ArrayValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\Representation\ValueRepresentation;

class JsonPlugin extends AbstractPlugin implements PluginCompleteInterface
{
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
        if (!isset($var[0]) || ('{' !== $var[0] && '[' !== $var[0])) {
            return $v;
        }

        try {
            $json = \json_decode($var, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return $v;
        }

        $json = (array) $json;

        $c = $v->getContext();

        $base = new BaseContext('JSON Decode');
        $base->depth = $c->getDepth();

        if (null !== ($ap = $c->getAccessPath())) {
            $base->access_path = 'json_decode('.$ap.', true)';
        }

        $json = $this->getParser()->parse($json, $base);

        if ($json instanceof ArrayValue && (~$json->flags & AbstractValue::FLAG_DEPTH_LIMIT) && $contents = $json->getContents()) {
            foreach ($contents as $value) {
                $value->flags |= AbstractValue::FLAG_GENERATED;
            }
            $v->addRepresentation(new ContainerRepresentation('Json', $contents), 0);
        } else {
            $json->flags |= AbstractValue::FLAG_GENERATED;
            $v->addRepresentation(new ValueRepresentation('Json', $json), 0);
        }

        return $v;
    }
}
