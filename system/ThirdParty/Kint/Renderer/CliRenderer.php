<?php

declare(strict_types=1);



namespace Kint\Renderer;

use Kint\Value\AbstractValue;
use Throwable;

class CliRenderer extends TextRenderer
{
    
    public static bool $cli_colors = true;

    
    public static bool $detect_width = true;

    
    public static int $min_terminal_width = 40;

    
    public static bool $force_utf8 = false;

    
    public static $windows_stream = null;

    protected static ?int $terminal_width = null;

    protected bool $windows_output = false;

    protected bool $colors = false;

    public function __construct()
    {
        parent::__construct();

        if (!self::$force_utf8 && KINT_WIN) {
            if (!\function_exists('sapi_windows_vt100_support')) {
                $this->windows_output = true;
            } else {
                $stream = self::$windows_stream;

                if (!$stream && \defined('STDOUT')) {
                    $stream = STDOUT;
                }

                if (!$stream) {
                    $this->windows_output = true;
                } else {
                    $this->windows_output = !\sapi_windows_vt100_support($stream);
                }
            }
        }

        if (null === self::$terminal_width) {
            if (self::$detect_width) {
                try {
                    $tput = KINT_WIN ? \exec('tput cols 2>nul') : \exec('tput cols 2>/dev/null');
                    if ((bool) $tput) {
                        
                        self::$terminal_width = (int) $tput;
                    }
                } catch (Throwable $t) {
                    self::$terminal_width = self::$default_width;
                }
            }

            if (!isset(self::$terminal_width) || self::$terminal_width < self::$min_terminal_width) {
                self::$terminal_width = self::$default_width;
            }
        }

        $this->colors = $this->windows_output ? false : self::$cli_colors;

        $this->header_width = self::$terminal_width;
    }

    public function colorValue(string $string): string
    {
        if (!$this->colors) {
            return $string;
        }

        return "\x1b[32m".\str_replace("\n", "\x1b[0m\n\x1b[32m", $string)."\x1b[0m";
    }

    public function colorType(string $string): string
    {
        if (!$this->colors) {
            return $string;
        }

        return "\x1b[35;1m".\str_replace("\n", "\x1b[0m\n\x1b[35;1m", $string)."\x1b[0m";
    }

    public function colorTitle(string $string): string
    {
        if (!$this->colors) {
            return $string;
        }

        return "\x1b[36m".\str_replace("\n", "\x1b[0m\n\x1b[36m", $string)."\x1b[0m";
    }

    public function renderTitle(AbstractValue $v): string
    {
        if ($this->windows_output) {
            return $this->utf8ToWindows(parent::renderTitle($v));
        }

        return parent::renderTitle($v);
    }

    public function preRender(): string
    {
        return PHP_EOL;
    }

    public function postRender(): string
    {
        if ($this->windows_output) {
            return $this->utf8ToWindows(parent::postRender());
        }

        return parent::postRender();
    }

    public function escape(string $string, $encoding = false): string
    {
        return \str_replace("\x1b", '\\x1b', $string);
    }

    protected function utf8ToWindows(string $string): string
    {
        return \str_replace(
            ['┌', '═', '┐', '│', '└', '─', '┘'],
            [' ', '=', ' ', '|', ' ', '-', ' '],
            $string
        );
    }
}
