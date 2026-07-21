<?php

declare(strict_types=1);



namespace Kint\Parser;

use DateTimeImmutable;
use Kint\Value\AbstractValue;
use Kint\Value\FixedWidthValue;
use Kint\Value\Representation\StringRepresentation;
use Kint\Value\StringValue;

class TimestampPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public static array $blacklist = [
        2147483648,
        2147483647,
        1073741824,
        1073741823,
    ];

    public function getTypes(): array
    {
        return ['string', 'integer'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if (\is_string($var) && !\ctype_digit($var)) {
            return $v;
        }

        if ($var < 0) {
            return $v;
        }

        if (\in_array($var, self::$blacklist, true)) {
            return $v;
        }

        $len = \strlen((string) $var);

        
        if ($len < 9 || $len > 10) {
            return $v;
        }

        if (!$v instanceof StringValue && !$v instanceof FixedWidthValue) {
            return $v;
        }

        if (!$dt = DateTimeImmutable::createFromFormat('U', (string) $var)) {
            return $v;
        }

        $v->removeRepresentation('contents');
        $v->addRepresentation(new StringRepresentation('Timestamp', $dt->format('c'), null, true));

        return $v;
    }
}
