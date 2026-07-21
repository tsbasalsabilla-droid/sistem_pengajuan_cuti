<?php

declare(strict_types=1);




return [
    'baseCastMissing'        => 'The "{0}" class must inherit the "CodeIgniter\Entity\Cast\BaseCast" class.',
    'enumInvalidCaseName'    => 'Invalid case name "{0}" for enum "{1}".',
    'enumInvalidType'        => 'Expected enum of type "{1}", but received "{0}".',
    'enumInvalidValue'       => 'Invalid value "{1}" for enum "{0}".',
    'enumMissingClass'       => 'Enum class must be specified for enum casting.',
    'enumNotEnum'            => 'The "{0}" is not a valid enum class.',
    'invalidCastMethod'      => 'The "{0}" is invalid cast method, valid methods are: ["get", "set"].',
    'invalidTimestamp'       => 'Type casting "timestamp" expects a correct timestamp.',
    'jsonErrorCtrlChar'      => 'Unexpected control character found.',
    'jsonErrorDepth'         => 'Maximum stack depth exceeded.',
    'jsonErrorStateMismatch' => 'Underflow or the modes mismatch.',
    'jsonErrorSyntax'        => 'Syntax error, malformed JSON.',
    'jsonErrorUnknown'       => 'Unknown error.',
    'jsonErrorUtf8'          => 'Malformed UTF-8 characters, possibly incorrectly encoded.',
];
