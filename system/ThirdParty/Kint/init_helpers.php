<?php

declare(strict_types=1);



use Kint\Kint;
use Kint\Renderer\CliRenderer;

if (!\function_exists('d')) {
    
    function d(...$args)
    {
        return Kint::dump(...$args);
    }

    Kint::$aliases[] = 'd';
}

if (!\function_exists('s')) {
    
    function s(...$args)
    {
        if (false === Kint::$enabled_mode) {
            return 0;
        }

        $kstash = Kint::$enabled_mode;
        $cstash = CliRenderer::$cli_colors;

        if (Kint::MODE_TEXT !== Kint::$enabled_mode) {
            Kint::$enabled_mode = Kint::MODE_PLAIN;

            if (PHP_SAPI === 'cli' && true === Kint::$cli_detection) {
                Kint::$enabled_mode = Kint::$mode_default_cli;
            }
        }

        CliRenderer::$cli_colors = false;

        $out = Kint::dump(...$args);

        Kint::$enabled_mode = $kstash;
        CliRenderer::$cli_colors = $cstash;

        return $out;
    }

    Kint::$aliases[] = 's';
}
