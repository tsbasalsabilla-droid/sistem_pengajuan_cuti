<?php

declare(strict_types=1);



use CodeIgniter\Exceptions\BadFunctionCallException;



if (! function_exists('number_to_size')) {
    
    function number_to_size($num, int $precision = 1, ?string $locale = null)
    {
        
        try {
            
            $num = 0 + str_replace(',', '', (string) $num);
        } catch (ErrorException) {
            
            return false;
        }

        
        $generalLocale = $locale;
        if ($locale !== null && $locale !== '' && ($underscorePos = strpos($locale, '_'))) {
            $generalLocale = substr($locale, 0, $underscorePos);
        }

        if ($num >= 1_000_000_000_000) {
            $num  = round($num / 1_099_511_627_776, $precision);
            $unit = lang('Number.terabyteAbbr', [], $generalLocale);
        } elseif ($num >= 1_000_000_000) {
            $num  = round($num / 1_073_741_824, $precision);
            $unit = lang('Number.gigabyteAbbr', [], $generalLocale);
        } elseif ($num >= 1_000_000) {
            $num  = round($num / 1_048_576, $precision);
            $unit = lang('Number.megabyteAbbr', [], $generalLocale);
        } elseif ($num >= 1000) {
            $num  = round($num / 1024, $precision);
            $unit = lang('Number.kilobyteAbbr', [], $generalLocale);
        } else {
            $unit = lang('Number.bytes', [], $generalLocale);
        }

        return format_number($num, $precision, $locale, ['after' => ' ' . $unit]);
    }
}

if (! function_exists('number_to_amount')) {
    
    function number_to_amount($num, int $precision = 0, ?string $locale = null)
    {
        
        try {
            
            $num = 0 + str_replace(',', '', (string) $num);
        } catch (ErrorException) {
            
            return false;
        }

        $suffix = '';

        
        $generalLocale = $locale;
        if ($locale !== null && $locale !== '' && ($underscorePos = strpos($locale, '_'))) {
            $generalLocale = substr($locale, 0, $underscorePos);
        }

        if ($num >= 1_000_000_000_000_000) {
            $suffix = lang('Number.quadrillion', [], $generalLocale);
            $num    = round(($num / 1_000_000_000_000_000), $precision);
        } elseif ($num >= 1_000_000_000_000) {
            $suffix = lang('Number.trillion', [], $generalLocale);
            $num    = round(($num / 1_000_000_000_000), $precision);
        } elseif ($num >= 1_000_000_000) {
            $suffix = lang('Number.billion', [], $generalLocale);
            $num    = round(($num / 1_000_000_000), $precision);
        } elseif ($num >= 1_000_000) {
            $suffix = lang('Number.million', [], $generalLocale);
            $num    = round(($num / 1_000_000), $precision);
        } elseif ($num >= 1000) {
            $suffix = lang('Number.thousand', [], $generalLocale);
            $num    = round(($num / 1000), $precision);
        }

        return format_number($num, $precision, $locale, ['after' => $suffix]);
    }
}

if (! function_exists('number_to_currency')) {
    function number_to_currency(float $num, string $currency, ?string $locale = null, int $fraction = 0): string
    {
        return format_number($num, 1, $locale, [
            'type'     => NumberFormatter::CURRENCY,
            'currency' => $currency,
            'fraction' => $fraction,
        ]);
    }
}

if (! function_exists('format_number')) {
    
    function format_number(float $num, int $precision = 1, ?string $locale = null, array $options = []): string
    {
        
        
        $locale ??= Locale::getDefault();

        
        $type = (int) ($options['type'] ?? NumberFormatter::DECIMAL);

        $formatter = new NumberFormatter($locale, $type);

        
        if ($type === NumberFormatter::CURRENCY) {
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, (float) $options['fraction']);
            $output = $formatter->formatCurrency($num, $options['currency']);
        } else {
            
            
            $pattern = '#,##0.' . str_repeat('#', $precision);

            $formatter->setPattern($pattern);
            $output = $formatter->format($num);
        }

        
        $output = trim($output, '. ');

        if (intl_is_failure($formatter->getErrorCode())) {
            throw new BadFunctionCallException($formatter->getErrorMessage());
        }

        
        if (isset($options['before']) && is_string($options['before'])) {
            $output = $options['before'] . $output;
        }

        if (isset($options['after']) && is_string($options['after'])) {
            $output .= $options['after'];
        }

        return $output;
    }
}

if (! function_exists('number_to_roman')) {
    
    function number_to_roman($num): ?string
    {
        static $map = [
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1,
        ];

        $num = (int) $num;

        if ($num < 1 || $num > 3999) {
            return null;
        }

        $result = '';

        foreach ($map as $roman => $arabic) {
            $repeat = (int) floor($num / $arabic);
            $result .= str_repeat($roman, $repeat);
            $num %= $arabic;
        }

        return $result;
    }
}
