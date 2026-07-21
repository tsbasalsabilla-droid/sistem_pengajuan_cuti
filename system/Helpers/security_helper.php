<?php

declare(strict_types=1);





if (! function_exists('sanitize_filename')) {
    
    function sanitize_filename(string $filename, bool $relativePath = false): string
    {
        
        $bad = [
            '../',
            '<!--',
            '-->',
            '<',
            '>',
            "'",
            '"',
            '&',
            '$',
            '#',
            '{',
            '}',
            '[',
            ']',
            '=',
            ';',
            '?',
            '%20',
            '%22',
            '%3c',
            '%253c',
            '%3e',
            '%0e',
            '%28',
            '%29',
            '%2528',
            '%26',
            '%24',
            '%3f',
            '%3b',
            '%3d',
        ];

        if (! $relativePath) {
            $bad[] = './';
            $bad[] = '/';
        }

        $filename = remove_invisible_characters($filename, false);

        do {
            $old      = $filename;
            $filename = str_replace($bad, '', $filename);
        } while ($old !== $filename);

        return stripslashes($filename);
    }
}

if (! function_exists('strip_image_tags')) {
    
    function strip_image_tags(string $str): string
    {
        return preg_replace(
            [
                '#<img[\s/]+.*?src\s*=\s*(["\'])([^\\1]+?)\\1.*?\>#i',
                '#<img[\s/]+.*?src\s*=\s*?(([^\s"\'=<>`]+)).*?\>#i',
            ],
            '\\2',
            $str,
        );
    }
}

if (! function_exists('encode_php_tags')) {
    
    function encode_php_tags(string $str): string
    {
        return str_replace(['<?', '?>'], ['&lt;?', '?&gt;'], $str);
    }
}
