<?php

declare(strict_types=1);





if (! function_exists('singular')) {
    
    function singular(string $string): string
    {
        $result = $string;

        if (! is_pluralizable($result)) {
            return $result;
        }

        
        $singularRules = [
            '/(matr)ices$/'                                                   => '\1ix',
            '/(vert|ind)ices$/'                                               => '\1ex',
            '/^(ox)en/'                                                       => '\1',
            '/(alias)es$/'                                                    => '\1',
            '/([octop|vir])i$/'                                               => '\1us',
            '/(cris|ax|test)es$/'                                             => '\1is',
            '/(shoe)s$/'                                                      => '\1',
            '/(o)es$/'                                                        => '\1',
            '/(bus|campus)es$/'                                               => '\1',
            '/([m|l])ice$/'                                                   => '\1ouse',
            '/(x|ch|ss|sh)es$/'                                               => '\1',
            '/(m)ovies$/'                                                     => '\1\2ovie',
            '/(s)eries$/'                                                     => '\1\2eries',
            '/([^aeiouy]|qu)ies$/'                                            => '\1y',
            '/([lr])ves$/'                                                    => '\1f',
            '/(tive)s$/'                                                      => '\1',
            '/(hive)s$/'                                                      => '\1',
            '/([^f])ves$/'                                                    => '\1fe',
            '/(^analy)ses$/'                                                  => '\1sis',
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/' => '\1\2sis',
            '/([ti])a$/'                                                      => '\1um',
            '/(p)eople$/'                                                     => '\1\2erson',
            '/(m)en$/'                                                        => '\1an',
            '/(s)tatuses$/'                                                   => '\1\2tatus',
            '/(c)hildren$/'                                                   => '\1\2hild',
            '/(n)ews$/'                                                       => '\1\2ews',
            '/(quiz)zes$/'                                                    => '\1',
            '/([^us])s$/'                                                     => '\1',
        ];

        foreach ($singularRules as $rule => $replacement) {
            if (preg_match($rule, $result)) {
                $result = preg_replace($rule, $replacement, $result);
                break;
            }
        }

        return $result;
    }
}

if (! function_exists('plural')) {
    
    function plural(string $string): string
    {
        $result = $string;

        if (! is_pluralizable($result)) {
            return $result;
        }

        $pluralRules = [
            '/(quiz)$/'               => '\1zes',    
            '/^(ox)$/'                => '\1\2en', 
            '/([m|l])ouse$/'          => '\1ice', 
            '/(matr|vert|ind)ix|ex$/' => '\1ices', 
            '/(x|ch|ss|sh)$/'         => '\1es', 
            '/([^aeiouy]|qu)y$/'      => '\1ies', 
            '/(hive)$/'               => '\1s', 
            '/(?:([^f])fe|([lr])f)$/' => '\1\2ves', 
            '/sis$/'                  => 'ses', 
            '/([ti])um$/'             => '\1a', 
            '/(p)erson$/'             => '\1eople', 
            '/(m)an$/'                => '\1en', 
            '/(c)hild$/'              => '\1hildren', 
            '/(buffal|tomat)o$/'      => '\1\2oes', 
            '/(bu|campu)s$/'          => '\1\2ses', 
            '/(alias|status|virus)$/' => '\1es', 
            '/(octop)us$/'            => '\1i', 
            '/(ax|cris|test)is$/'     => '\1es', 
            '/s$/'                    => 's', 
            '/$/'                     => 's',
        ];

        foreach ($pluralRules as $rule => $replacement) {
            if (preg_match($rule, $result)) {
                $result = preg_replace($rule, $replacement, $result);
                break;
            }
        }

        return $result;
    }
}

if (! function_exists('counted')) {
    
    function counted(int $count, string $string): string
    {
        $result = "{$count} ";

        return $result . ($count === 1 ? singular($string) : plural($string));
    }
}

if (! function_exists('camelize')) {
    
    function camelize(string $string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(preg_replace('/[\s_]+/', ' ', $string))));
    }
}

if (! function_exists('pascalize')) {
    
    function pascalize(string $string): string
    {
        return ucfirst(camelize($string));
    }
}

if (! function_exists('underscore')) {
    
    function underscore(string $string): string
    {
        $replacement = trim($string);

        return preg_replace('/[\s]+/', '_', $replacement);
    }
}

if (! function_exists('decamelize')) {
    
    function decamelize(string $string): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', trim($string)));
    }
}

if (! function_exists('humanize')) {
    
    function humanize(string $string, string $separator = '_'): string
    {
        $replacement = trim($string);

        return ucwords(preg_replace('/[' . $separator . ']+/', ' ', $replacement));
    }
}

if (! function_exists('is_pluralizable')) {
    
    function is_pluralizable(string $word): bool
    {
        $uncountables = in_array(
            strtolower($word),
            [
                'advice',
                'bravery',
                'butter',
                'chaos',
                'clarity',
                'coal',
                'courage',
                'cowardice',
                'curiosity',
                'education',
                'equipment',
                'evidence',
                'fish',
                'fun',
                'furniture',
                'greed',
                'help',
                'homework',
                'honesty',
                'information',
                'insurance',
                'jewelry',
                'knowledge',
                'livestock',
                'love',
                'luck',
                'marketing',
                'meta',
                'money',
                'mud',
                'news',
                'patriotism',
                'racism',
                'rice',
                'satisfaction',
                'scenery',
                'series',
                'sexism',
                'silence',
                'species',
                'spelling',
                'sugar',
                'water',
                'weather',
                'wisdom',
                'work',
            ],
            true,
        );

        return ! $uncountables;
    }
}

if (! function_exists('dasherize')) {
    
    function dasherize(string $string): string
    {
        return str_replace('_', '-', $string);
    }
}

if (! function_exists('ordinal')) {
    
    function ordinal(int $integer): string
    {
        $suffixes = [
            'th',
            'st',
            'nd',
            'rd',
            'th',
            'th',
            'th',
            'th',
            'th',
            'th',
        ];

        return $integer % 100 >= 11 && $integer % 100 <= 13 ? 'th' : $suffixes[$integer % 10];
    }
}

if (! function_exists('ordinalize')) {
    
    function ordinalize(int $integer): string
    {
        return $integer . ordinal($integer);
    }
}
