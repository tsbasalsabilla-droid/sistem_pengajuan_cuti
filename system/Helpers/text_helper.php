<?php

declare(strict_types=1);



use CodeIgniter\Exceptions\InvalidArgumentException;
use Config\ForeignCharacters;



if (! function_exists('word_limiter')) {
    
    function word_limiter(string $str, int $limit = 100, string $endChar = '&#8230;'): string
    {
        if (trim($str) === '') {
            return $str;
        }

        preg_match('/^\s*+(?:\S++\s*+){1,' . $limit . '}/', $str, $matches);

        if (strlen($str) === strlen($matches[0])) {
            $endChar = '';
        }

        return rtrim($matches[0]) . $endChar;
    }
}

if (! function_exists('character_limiter')) {
    
    function character_limiter(string $string, int $limit = 500, string $endChar = '&#8230;'): string
    {
        if (mb_strlen($string) < $limit) {
            return $string;
        }

        
        $string       = preg_replace('/ {2,}/', ' ', str_replace(["\r", "\n", "\t", "\x0B", "\x0C"], ' ', $string));
        $stringLength = mb_strlen($string);

        if ($stringLength <= $limit) {
            return $string;
        }

        $output       = '';
        $outputLength = 0;
        $words        = explode(' ', trim($string));

        foreach ($words as $word) {
            $output .= $word . ' ';
            $outputLength = mb_strlen($output);

            if ($outputLength >= $limit) {
                $output = trim($output);
                break;
            }
        }

        return ($outputLength === $stringLength) ? $output : $output . $endChar;
    }
}

if (! function_exists('ascii_to_entities')) {
    
    function ascii_to_entities(string $str): string
    {
        $out = '';

        for ($i = 0, $s = strlen($str) - 1, $count = 1, $temp = []; $i <= $s; $i++) {
            $ordinal = ord($str[$i]);

            if ($ordinal < 128) {
                
                if (count($temp) === 1) {
                    $out .= '&#' . array_shift($temp) . ';';
                    $count = 1;
                }

                $out .= $str[$i];
            } else {
                if ($temp === []) {
                    $count = ($ordinal < 224) ? 2 : 3;
                }

                $temp[] = $ordinal;

                if (count($temp) === $count) {
                    $number = ($count === 3) ? (($temp[0] % 16) * 4096) + (($temp[1] % 64) * 64) + ($temp[2] % 64) : (($temp[0] % 32) * 64) + ($temp[1] % 64);
                    $out .= '&#' . $number . ';';
                    $count = 1;
                    $temp  = [];
                }
                
                elseif ($i === $s) {
                    $out .= '&#' . implode(';', $temp) . ';';
                }
            }
        }

        return $out;
    }
}

if (! function_exists('entities_to_ascii')) {
    
    function entities_to_ascii(string $str, bool $all = true): string
    {
        if (preg_match_all('/\&#(\d+)\;/', $str, $matches) >= 1) {
            for ($i = 0, $s = count($matches[0]); $i < $s; $i++) {
                $digits = (int) $matches[1][$i];
                $out    = '';
                if ($digits < 128) {
                    $out .= chr($digits);
                } elseif ($digits < 2048) {
                    $out .= chr(192 + (($digits - ($digits % 64)) / 64)) . chr(128 + ($digits % 64));
                } else {
                    $out .= chr(224 + (($digits - ($digits % 4096)) / 4096))
                            . chr(128 + ((($digits % 4096) - ($digits % 64)) / 64))
                            . chr(128 + ($digits % 64));
                }
                $str = str_replace($matches[0][$i], $out, $str);
            }
        }

        if ($all) {
            return str_replace(
                ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;', '&#45;'],
                ['&', '<', '>', '"', "'", '-'],
                $str,
            );
        }

        return $str;
    }
}

if (! function_exists('word_censor')) {
    
    function word_censor(string $str, array $censored, string $replacement = ''): string
    {
        if ($censored === []) {
            return $str;
        }

        $str = ' ' . $str . ' ';

        
        
        
        
        $delim = '[-_\'\"`(){}<>\[\]|!?@#%&,.:;^~*+=\/ 0-9\n\r\t]';

        foreach ($censored as $badword) {
            $badword = str_replace('\*', '\w*?', preg_quote($badword, '/'));

            if ($replacement !== '') {
                $str = preg_replace(
                    "/({$delim})(" . $badword . ")({$delim})/i",
                    "\\1{$replacement}\\3",
                    $str,
                );
            } elseif (preg_match_all("/{$delim}(" . $badword . "){$delim}/i", $str, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE) >= 1) {
                $matches = $matches[1];

                for ($i = count($matches) - 1; $i >= 0; $i--) {
                    $length = strlen($matches[$i][0]);

                    $str = substr_replace(
                        $str,
                        str_repeat('#', $length),
                        $matches[$i][1],
                        $length,
                    );
                }
            }
        }

        return trim($str);
    }
}

if (! function_exists('highlight_code')) {
    
    function highlight_code(string $str): string
    {
        
        $str = str_replace(
            ['&lt;', '&gt;', '<?', '?>', '<%', '%>', '\\', '</script>'],
            ['<', '>', 'phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'],
            $str,
        );

        
        
        $str = highlight_string('<?php ' . $str . ' ?>', true);

        
        $str = preg_replace(
            [
                '/<span style="color: #([A-Z0-9]+)">&lt;\?php(&nbsp;| )/i',
                '/(<span style="color: #[A-Z0-9]+">.*?)\?&gt;<\/span>\n<\/span>\n<\/code>/is',
                '/<span style="color: #[A-Z0-9]+"\><\/span>/i',
            ],
            [
                '<span style="color: #$1">',
                "$1</span>\n</span>\n</code>",
                '',
            ],
            $str,
        );

        
        return str_replace(
            [
                'phptagopen',
                'phptagclose',
                'asptagopen',
                'asptagclose',
                'backslashtmp',
                'scriptclose',
            ],
            [
                '&lt;?',
                '?&gt;',
                '&lt;%',
                '%&gt;',
                '\\',
                '&lt;/script&gt;',
            ],
            $str,
        );
    }
}

if (! function_exists('highlight_phrase')) {
    
    function highlight_phrase(string $str, string $phrase, string $tagOpen = '<mark>', string $tagClose = '</mark>'): string
    {
        return ($str !== '' && $phrase !== '') ? preg_replace('/(' . preg_quote($phrase, '/') . ')/i', $tagOpen . '\\1' . $tagClose, $str) : $str;
    }
}

if (! function_exists('convert_accented_characters')) {
    
    function convert_accented_characters(string $str): string
    {
        static $arrayFrom, $arrayTo;

        if (! is_array($arrayFrom)) {
            $config = new ForeignCharacters();

            if ($config->characterList === [] || ! is_array($config->characterList)) {
                $arrayFrom = [];
                $arrayTo   = [];

                return $str;
            }
            $arrayFrom = array_keys($config->characterList);
            $arrayTo   = array_values($config->characterList);

            unset($config);
        }

        return preg_replace($arrayFrom, $arrayTo, $str);
    }
}

if (! function_exists('word_wrap')) {
    
    function word_wrap(string $str, int $charlim = 76): string
    {
        
        $str = preg_replace('| +|', ' ', $str);

        
        if (str_contains($str, "\r")) {
            $str = str_replace(["\r\n", "\r"], "\n", $str);
        }

        
        
        $unwrap = [];

        if (preg_match_all('|\{unwrap\}(.+?)\{/unwrap\}|s', $str, $matches) >= 1) {
            for ($i = 0, $c = count($matches[0]); $i < $c; $i++) {
                $unwrap[] = $matches[1][$i];
                $str      = str_replace($matches[0][$i], '{{unwrapped' . $i . '}}', $str);
            }
        }

        
        
        
        $str = wordwrap($str, $charlim, "\n", false);

        
        $output = '';

        foreach (explode("\n", $str) as $line) {
            
            
            if (mb_strlen($line) <= $charlim) {
                $output .= $line . "\n";

                continue;
            }

            $temp = '';

            while (mb_strlen($line) > $charlim) {
                
                if (preg_match('!\[url.+\]|://|www\.!', $line)) {
                    break;
                }
                
                $temp .= mb_substr($line, 0, $charlim - 1);
                $line = mb_substr($line, $charlim - 1);
            }

            
            
            if ($temp !== '') {
                $output .= $temp . "\n" . $line . "\n";
            } else {
                $output .= $line . "\n";
            }
        }

        
        foreach ($unwrap as $key => $val) {
            $output = str_replace('{{unwrapped' . $key . '}}', $val, $output);
        }

        
        return rtrim($output);
    }
}

if (! function_exists('ellipsize')) {
    
    function ellipsize(string $str, int $maxLength, $position = 1, string $ellipsis = '&hellip;'): string
    {
        
        $str = trim(strip_tags($str));

        
        if (mb_strlen($str) <= $maxLength) {
            return $str;
        }

        $beg      = mb_substr($str, 0, (int) floor($maxLength * $position));
        $position = ($position > 1) ? 1 : $position;

        if ($position === 1) {
            $end = mb_substr($str, 0, -($maxLength - mb_strlen($beg)));
        } else {
            $end = mb_substr($str, -($maxLength - mb_strlen($beg)));
        }

        return $beg . $ellipsis . $end;
    }
}

if (! function_exists('strip_slashes')) {
    
    function strip_slashes($str)
    {
        if (! is_array($str)) {
            return stripslashes($str);
        }

        foreach ($str as $key => $val) {
            $str[$key] = strip_slashes($val);
        }

        return $str;
    }
}

if (! function_exists('strip_quotes')) {
    
    function strip_quotes(string $str): string
    {
        return str_replace(['"', "'"], '', $str);
    }
}

if (! function_exists('quotes_to_entities')) {
    
    function quotes_to_entities(string $str): string
    {
        return str_replace(["\\'", '"', "'", '"'], ['&#39;', '&quot;', '&#39;', '&quot;'], $str);
    }
}

if (! function_exists('reduce_double_slashes')) {
    
    function reduce_double_slashes(string $str): string
    {
        return preg_replace('#(^|[^:])//+#', '\\1/', $str);
    }
}

if (! function_exists('reduce_multiples')) {
    
    function reduce_multiples(string $str, string $character = ',', bool $trim = false): string
    {
        $pattern = '#' . preg_quote($character, '#') . '{2,}#';
        $str     = preg_replace($pattern, $character, $str);

        return $trim ? trim($str, $character) : $str;
    }
}

if (! function_exists('random_string')) {
    
    function random_string(string $type = 'alnum', int $len = 8): string
    {
        switch ($type) {
            case 'alnum':
            case 'nozero':
            case 'alpha':
                switch ($type) {
                    case 'alpha':
                        $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        break;

                    case 'alnum':
                        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        break;

                    case 'nozero':
                        $pool = '123456789';
                        break;
                }

                return _from_random($len, $pool);

            case 'numeric':
                $max  = 10 ** $len - 1;
                $rand = random_int(0, $max);

                return sprintf('%0' . $len . 'd', $rand);

            case 'crypto':
                if ($len % 2 !== 0) {
                    throw new InvalidArgumentException(
                        'You must set an even number to the second parameter when you use `crypto`.',
                    );
                }

                return bin2hex(random_bytes($len / 2));
        }

        throw new InvalidArgumentException(
            sprintf(
                'Invalid type "%s". Accepted types: alpha, alnum, numeric, nozero, or crypto.',
                $type,
            ),
        );
    }
}

if (! function_exists('_from_random')) {
    
    function _from_random(int $length, string $pool): string
    {
        if ($length <= 0) {
            throw new InvalidArgumentException(
                sprintf('A strictly positive length is expected, "%d" given.', $length),
            );
        }

        $poolSize = \strlen($pool);
        $bits     = (int) ceil(log($poolSize, 2.0));
        if ($bits <= 0 || $bits > 56) {
            throw new InvalidArgumentException(
                'The length of the alphabet must in the [2^1, 2^56] range.',
            );
        }

        $string = '';

        while ($length > 0) {
            $urandomLength = (int) ceil(2 * $length * $bits / 8.0);
            $data          = random_bytes($urandomLength);
            $unpackedData  = 0;
            $unpackedBits  = 0;

            for ($i = 0; $i < $urandomLength && $length > 0; $i++) {
                
                $unpackedData = ($unpackedData << 8) | \ord($data[$i]);
                $unpackedBits += 8;

                
                
                for (; $unpackedBits >= $bits && $length > 0; $unpackedBits -= $bits) {
                    $index = ($unpackedData & ((1 << $bits) - 1));
                    $unpackedData >>= $bits;
                    
                    
                    
                    if ($index < $poolSize) {
                        $string .= $pool[$index];
                        $length--;
                    }
                }
            }
        }

        return $string;
    }
}

if (! function_exists('increment_string')) {
    
    function increment_string(string $str, string $separator = '_', int $first = 1): string
    {
        preg_match('/(.+)' . preg_quote($separator, '/') . '([0-9]+)$/', $str, $match);

        return isset($match[2]) ? $match[1] . $separator . ((int) $match[2] + 1) : $str . $separator . $first;
    }
}

if (! function_exists('alternator')) {
    
    function alternator(...$args): string
    {
        static $i;

        if (func_num_args() === 0) {
            $i = 0;

            return '';
        }

        return $args[($i++ % count($args))];
    }
}

if (! function_exists('excerpt')) {
    
    function excerpt(string $text, ?string $phrase = null, int $radius = 100, string $ellipsis = '...'): string
    {
        if (isset($phrase)) {
            $phrasePosition = mb_stripos($text, $phrase);
            $phraseLength   = mb_strlen($phrase);
        } else {
            $phrasePosition = $radius / 2;
            $phraseLength   = 1;
        }

        $beforeWords = explode(' ', mb_substr($text, 0, $phrasePosition));
        $afterWords  = explode(' ', mb_substr($text, $phrasePosition + $phraseLength));

        $firstPartOutput = ' ';
        $endPartOutput   = ' ';
        $count           = 0;

        foreach (array_reverse($beforeWords) as $beforeWord) {
            $beforeWordLength = mb_strlen($beforeWord);

            if (($beforeWordLength + $count + 1) < $radius) {
                $firstPartOutput = ' ' . $beforeWord . $firstPartOutput;
            }

            $count = ++$count + $beforeWordLength;
        }

        $count = 0;

        foreach ($afterWords as $afterWord) {
            $afterWordLength = mb_strlen($afterWord);

            if (($afterWordLength + $count + 1) < $radius) {
                $endPartOutput .= $afterWord . ' ';
            }

            $count = ++$count + $afterWordLength;
        }

        $ellPre = $phrase !== null ? $ellipsis : '';

        return str_replace('  ', ' ', $ellPre . $firstPartOutput . $phrase . $endPartOutput . $ellipsis);
    }
}
