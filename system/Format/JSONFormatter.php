<?php

declare(strict_types=1);



namespace CodeIgniter\Format;

use CodeIgniter\Format\Exceptions\FormatException;
use Config\Format;


class JSONFormatter implements FormatterInterface
{
    
    public function format($data)
    {
        $config = new Format();

        $options = $config->formatterOptions['application/json'] ?? JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $options |= JSON_PARTIAL_OUTPUT_ON_ERROR;

        if (ENVIRONMENT !== 'production') {
            $options |= JSON_PRETTY_PRINT;
        }

        $result = json_encode($data, $options, $config->jsonEncodeDepth ?? 512);

        if (! in_array(json_last_error(), [JSON_ERROR_NONE, JSON_ERROR_RECURSION], true)) {
            throw FormatException::forInvalidJSON(json_last_error_msg());
        }

        return $result;
    }
}
