<?php

declare(strict_types=1);



namespace Kint\Renderer\Rich;

use Kint\Value\AbstractValue;
use Kint\Value\Representation\BinaryRepresentation;
use Kint\Value\Representation\RepresentationInterface;

class BinaryPlugin extends AbstractPlugin implements TabPluginInterface
{
    
    public static int $line_length = 0x10;
    
    public static int $chunk_length = 0x2;

    public function renderTab(RepresentationInterface $r, AbstractValue $v): ?string
    {
        if (!$r instanceof BinaryRepresentation) {
            return null;
        }

        $out = '<pre>';

        $lines = \str_split($r->getValue(), self::$line_length);

        foreach ($lines as $index => $line) {
            $out .= ((string) \sprintf('%08X', $index * self::$line_length)).":\t";

            $chunks = \str_split(\str_pad(\bin2hex($line), 2 * self::$line_length, ' '), 2 * self::$chunk_length);

            $out .= \implode(' ', $chunks);
            $out .= "\t".\preg_replace('/[^\\x20-\\x7E]/', '.', $line)."\n";
        }

        $out .= '</pre>';

        return $out;
    }
}
