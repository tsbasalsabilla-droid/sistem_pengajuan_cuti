<?php

declare(strict_types=1);





if (! function_exists('xml_convert')) {
    
    function xml_convert(string $str, bool $protectAll = false): string
    {
        $temp = '__TEMP_AMPERSANDS__';

        
        
        $str = preg_replace('/&#(\d+);/', $temp . '\\1;', $str);

        if ($protectAll) {
            $str = preg_replace('/&(\w+);/', $temp . '\\1;', $str);
        }

        $original = [
            '&',
            '<',
            '>',
            '"',
            "'",
            '-',
        ];

        $replacement = [
            '&amp;',
            '&lt;',
            '&gt;',
            '&quot;',
            '&apos;',
            '&#45;',
        ];

        $str = str_replace($original, $replacement, $str);

        
        $str = preg_replace('/' . $temp . '(\d+);/', '&#\\1;', $str);

        if ($protectAll) {
            return preg_replace('/' . $temp . '(\w+);/', '&\\1;', $str);
        }

        return $str;
    }
}
