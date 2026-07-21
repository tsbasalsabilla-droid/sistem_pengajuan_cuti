<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Representation\SplFileInfoRepresentation;
use SplFileInfo;
use TypeError;

class FsPathPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public static array $blacklist = ['/', '.'];

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
        if (\strlen($var) > 2048) {
            return $v;
        }

        if (!\preg_match('/[\\/\\'.DIRECTORY_SEPARATOR.']/', $var)) {
            return $v;
        }

        if (\preg_match('/[?<>"*|]/', $var)) {
            return $v;
        }

        try {
            if (!@\file_exists($var)) {
                return $v;
            }
        } catch (TypeError $e) {
            
            return $v; 
        }

        if (\in_array($var, self::$blacklist, true)) {
            return $v;
        }

        $v->addRepresentation(new SplFileInfoRepresentation(new SplFileInfo($var)), 0);

        return $v;
    }
}
