<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Filters;

use php_user_filter;


class CITestStreamFilter extends php_user_filter
{
    
    public static $buffer = '';

    protected static bool $registered = false;

    
    private static $err;

    
    private static $out;

    
    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            static::$buffer .= $bucket->data;
            $consumed += (int) $bucket->datalen;
        }

        return PSFS_PASS_ON;
    }

    public static function registration(): void
    {
        if (! static::$registered) {
            static::$registered = stream_filter_register('CITestStreamFilter', self::class); 
        }

        static::$buffer = '';
    }

    public static function addErrorFilter(): void
    {
        self::removeFilter(self::$err);
        self::$err = stream_filter_append(STDERR, 'CITestStreamFilter');
    }

    public static function addOutputFilter(): void
    {
        self::removeFilter(self::$out);
        self::$out = stream_filter_append(STDOUT, 'CITestStreamFilter');
    }

    public static function removeErrorFilter(): void
    {
        self::removeFilter(self::$err);
    }

    public static function removeOutputFilter(): void
    {
        self::removeFilter(self::$out);
    }

    
    protected static function removeFilter(&$stream): void
    {
        if ($stream !== null) {
            stream_filter_remove($stream);
            $stream = null;
        }
    }
}
