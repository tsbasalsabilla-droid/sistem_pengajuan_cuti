<?php

declare(strict_types=1);



namespace CodeIgniter\CLI;

use CodeIgniter\CLI\Exceptions\CLIException;
use CodeIgniter\Exceptions\InvalidArgumentException;
use Throwable;


class CLI
{
    
    public static $readline_support = false;

    
    public static $wait_msg = 'Press any key to continue...';

    
    protected static $initialized = false;

    
    protected static $foreground_colors = [
        'black'        => '0;30',
        'dark_gray'    => '1;30',
        'blue'         => '0;34',
        'dark_blue'    => '0;34',
        'light_blue'   => '1;34',
        'green'        => '0;32',
        'light_green'  => '1;32',
        'cyan'         => '0;36',
        'light_cyan'   => '1;36',
        'red'          => '0;31',
        'light_red'    => '1;31',
        'purple'       => '0;35',
        'light_purple' => '1;35',
        'yellow'       => '0;33',
        'light_yellow' => '1;33',
        'light_gray'   => '0;37',
        'white'        => '1;37',
    ];

    
    protected static $background_colors = [
        'black'      => '40',
        'red'        => '41',
        'green'      => '42',
        'yellow'     => '43',
        'blue'       => '44',
        'magenta'    => '45',
        'cyan'       => '46',
        'light_gray' => '47',
    ];

    
    protected static $segments = [];

    
    protected static $options = [];

    
    protected static $lastWrite;

    
    protected static $height;

    
    protected static $width;

    
    protected static $isColored = false;

    
    protected static ?InputOutput $io = null;

    
    public static function init()
    {
        if (is_cli()) {
            
            
            
            static::$readline_support = extension_loaded('readline');

            
            static::$segments = [];
            static::$options  = [];

            
            static::$isColored = static::hasColorSupport(STDOUT);

            static::parseCommandLine();

            static::$initialized = true;
        } elseif (! defined('STDOUT')) {
            
            
            
            define('STDOUT', 'php://output'); 
        }

        static::resetInputOutput();
    }

    
    public static function input(?string $prefix = null): string
    {
        return static::$io->input($prefix);
    }

    
    public static function prompt(string $field, $options = null, $validation = null): string
    {
        $extraOutput = '';
        $default     = '';

        if (isset($validation) && ! is_array($validation) && ! is_string($validation)) {
            throw new InvalidArgumentException('$rules can only be of type string|array');
        }

        if (! is_array($validation)) {
            $validation = ($validation !== null) ? explode('|', $validation) : [];
        }

        if (is_string($options)) {
            $extraOutput = ' [' . static::color($options, 'green') . ']';
            $default     = $options;
        }

        if (is_array($options) && $options !== []) {
            $opts               = $options;
            $extraOutputDefault = static::color((string) $opts[0], 'green');

            unset($opts[0]);

            if ($opts === []) {
                $extraOutput = $extraOutputDefault;
            } else {
                $extraOutput  = '[' . $extraOutputDefault . ', ' . implode(', ', $opts) . ']';
                $validation[] = 'in_list[' . implode(', ', $options) . ']';
            }

            $default = $options[0];
        }

        static::fwrite(STDOUT, $field . (trim($field) !== '' ? ' ' : '') . $extraOutput . ': ');

        
        $input = trim(static::$io->input());
        $input = ($input === '') ? (string) $default : $input;

        if ($validation !== []) {
            while (! static::validate('"' . trim($field) . '"', $input, $validation)) {
                $input = static::prompt($field, $options, $validation);
            }
        }

        return $input;
    }

    
    public static function promptByKey($text, array $options, $validation = null): string
    {
        if (is_string($text)) {
            $text = [$text];
        } elseif (! is_array($text)) {
            throw new InvalidArgumentException('$text can only be of type string|array');
        }

        CLI::isZeroOptions($options);

        if (($line = array_shift($text)) !== null) {
            CLI::write($line);
        }

        CLI::printKeysAndValues($options);

        return static::prompt(PHP_EOL . array_shift($text), array_keys($options), $validation);
    }

    
    public static function promptByMultipleKeys(string $text, array $options): array
    {
        CLI::isZeroOptions($options);

        $extraOutputDefault = static::color('0', 'green');
        $opts               = $options;
        unset($opts[0]);

        if ($opts === []) {
            $extraOutput = $extraOutputDefault;
        } else {
            $optsKey     = array_keys($opts);
            $extraOutput = '[' . $extraOutputDefault . ', ' . implode(', ', $optsKey) . ']';
            $extraOutput = 'You can specify multiple values separated by commas.' . PHP_EOL . $extraOutput;
        }

        CLI::write($text);
        CLI::printKeysAndValues($options);
        CLI::newLine();

        $input = static::prompt($extraOutput);
        $input = ($input === '') ? '0' : $input; 

        
        while (true) {
            $pattern = preg_match_all('/^\d+(,\d+)*$/', trim($input));

            
            $inputToArray = array_map(static fn ($value): int => (int) $value, explode(',', $input));
            
            $maxOptions = array_key_last($options);
            
            $maxInput = max($inputToArray);

            
            
            
            if ($pattern < 1 || $maxOptions < $maxInput) {
                static::error('Please select correctly.');
                CLI::newLine();

                $input = static::prompt($extraOutput);
                $input = ($input === '') ? '0' : $input;
            } else {
                break;
            }
        }

        $input = [];

        foreach ($options as $key => $description) {
            foreach ($inputToArray as $inputKey) {
                if ($key === $inputKey) {
                    $input[$key] = $description;
                }
            }
        }

        return $input;
    }

    
    
    

    
    private static function isZeroOptions(array $options): void
    {
        if ($options === []) {
            throw new InvalidArgumentException('No options to select from were provided');
        }
    }

    
    private static function printKeysAndValues(array $options): void
    {
        
        $keyMaxLength = max(array_map(mb_strwidth(...), array_keys($options))) + 2;

        foreach ($options as $key => $description) {
            $name = str_pad('  [' . $key . ']  ', $keyMaxLength + 4, ' ');
            CLI::write(CLI::color($name, 'green') . CLI::wrap($description, 125, $keyMaxLength + 4));
        }
    }

    
    
    

    
    protected static function validate(string $field, string $value, $rules): bool
    {
        $label      = $field;
        $field      = 'temp';
        $validation = service('validation', null, false);
        $validation->setRules([
            $field => [
                'label' => $label,
                'rules' => $rules,
            ],
        ]);
        $validation->run([$field => $value]);

        if ($validation->hasError($field)) {
            static::error($validation->getError($field));

            return false;
        }

        return true;
    }

    
    public static function print(string $text = '', ?string $foreground = null, ?string $background = null)
    {
        if ((string) $foreground !== '' || (string) $background !== '') {
            $text = static::color($text, $foreground, $background);
        }

        static::$lastWrite = null;

        static::fwrite(STDOUT, $text);
    }

    
    public static function write(string $text = '', ?string $foreground = null, ?string $background = null)
    {
        if ((string) $foreground !== '' || (string) $background !== '') {
            $text = static::color($text, $foreground, $background);
        }

        if (static::$lastWrite !== 'write') {
            $text              = PHP_EOL . $text;
            static::$lastWrite = 'write';
        }

        static::fwrite(STDOUT, $text . PHP_EOL);
    }

    
    public static function error(string $text, string $foreground = 'light_red', ?string $background = null)
    {
        
        $stdout            = static::$isColored;
        static::$isColored = static::hasColorSupport(STDERR);

        if ($foreground !== '' || (string) $background !== '') {
            $text = static::color($text, $foreground, $background);
        }

        static::fwrite(STDERR, $text . PHP_EOL);

        
        static::$isColored = $stdout;
    }

    
    public static function beep(int $num = 1)
    {
        echo str_repeat("\x07", $num);
    }

    
    public static function wait(int $seconds, bool $countdown = false)
    {
        if ($countdown) {
            $time = $seconds;

            while ($time > 0) {
                static::fwrite(STDOUT, $time . '... ');
                sleep(1);
                $time--;
            }

            static::write();
        } elseif ($seconds > 0) {
            sleep($seconds);
        } else {
            static::write(static::$wait_msg);
            static::$io->input();
        }
    }

    
    public static function isWindows(): bool
    {
        return is_windows();
    }

    
    public static function newLine(int $num = 1)
    {
        
        for ($i = 0; $i < $num; $i++) {
            static::write();
        }
    }

    
    public static function clearScreen()
    {
        
        
        is_windows() && ! static::streamSupports('sapi_windows_vt100_support', STDOUT)
            ? static::newLine(40)
            : static::fwrite(STDOUT, "\033[H\033[2J");
    }

    
    public static function color(string $text, string $foreground, ?string $background = null, ?string $format = null): string
    {
        if (! static::$isColored || $text === '') {
            return $text;
        }

        if (! array_key_exists($foreground, static::$foreground_colors)) {
            throw CLIException::forInvalidColor('foreground', $foreground);
        }

        if ((string) $background !== '' && ! array_key_exists($background, static::$background_colors)) {
            throw CLIException::forInvalidColor('background', $background);
        }

        $newText = '';

        
        if (str_contains($text, "\033[0m")) {
            $pattern = '/\\033\\[0;.+?\\033\\[0m/u';

            preg_match_all($pattern, $text, $matches);
            $coloredStrings = $matches[0];

            
            if ($coloredStrings === []) {
                return $newText . self::getColoredText($text, $foreground, $background, $format);
            }

            $nonColoredText = preg_replace(
                $pattern,
                '<<__colored_string__>>',
                $text,
            );
            $nonColoredChunks = preg_split(
                '/<<__colored_string__>>/u',
                $nonColoredText,
            );

            foreach ($nonColoredChunks as $i => $chunk) {
                if ($chunk !== '') {
                    $newText .= self::getColoredText($chunk, $foreground, $background, $format);
                }

                if (isset($coloredStrings[$i])) {
                    $newText .= $coloredStrings[$i];
                }
            }
        } else {
            $newText .= self::getColoredText($text, $foreground, $background, $format);
        }

        return $newText;
    }

    private static function getColoredText(string $text, string $foreground, ?string $background, ?string $format): string
    {
        $string = "\033[" . static::$foreground_colors[$foreground] . 'm';

        if ((string) $background !== '') {
            $string .= "\033[" . static::$background_colors[$background] . 'm';
        }

        if ($format === 'underline') {
            $string .= "\033[4m";
        }

        return $string . $text . "\033[0m";
    }

    
    public static function strlen(?string $string): int
    {
        if ((string) $string === '') {
            return 0;
        }

        foreach (static::$foreground_colors as $color) {
            $string = strtr($string, ["\033[" . $color . 'm' => '']);
        }

        foreach (static::$background_colors as $color) {
            $string = strtr($string, ["\033[" . $color . 'm' => '']);
        }

        $string = strtr($string, ["\033[4m" => '', "\033[0m" => '']);

        return mb_strwidth($string);
    }

    
    public static function streamSupports(string $function, $resource): bool
    {
        if (ENVIRONMENT === 'testing') {
            
            
            
            return function_exists($function);
        }

        return function_exists($function) && @$function($resource); 
    }

    
    public static function hasColorSupport($resource): bool
    {
        
        if (isset($_SERVER['NO_COLOR']) || getenv('NO_COLOR') !== false) {
            return false;
        }

        if (getenv('TERM_PROGRAM') === 'Hyper') {
            return true;
        }

        if (is_windows()) {
            
            return static::streamSupports('sapi_windows_vt100_support', $resource)
                || isset($_SERVER['ANSICON'])
                || getenv('ANSICON') !== false
                || getenv('ConEmuANSI') === 'ON'
                || getenv('TERM') === 'xterm';
            
        }

        return static::streamSupports('stream_isatty', $resource);
    }

    
    public static function getWidth(int $default = 80): int
    {
        if (static::$width === null) {
            static::generateDimensions();
        }

        return static::$width ?: $default;
    }

    
    public static function getHeight(int $default = 32): int
    {
        if (static::$height === null) {
            static::generateDimensions();
        }

        return static::$height ?: $default;
    }

    
    public static function generateDimensions()
    {
        try {
            if (is_windows()) {
                
                
                if (getenv('TERM') || (($shell = getenv('SHELL')) && preg_match('/(?:bash|zsh)(?:\.exe)?$/', $shell))) {
                    static::$height = (int) exec('tput lines');
                    static::$width  = (int) exec('tput cols');
                } else {
                    $return = -1;
                    $output = [];
                    exec('mode CON', $output, $return);

                    
                    
                    if ($return === 0 && $output !== [] && preg_match('/:\s*(\d+)\n[^:]+:\s*(\d+)\n/', implode("\n", $output), $matches)) {
                        static::$height = (int) $matches[1];
                        static::$width  = (int) $matches[2];
                    }
                }
            } elseif (($size = exec('stty size')) && preg_match('/(\d+)\s+(\d+)/', $size, $matches)) {
                static::$height = (int) $matches[1];
                static::$width  = (int) $matches[2];
            } else {
                static::$height = (int) exec('tput lines');
                static::$width  = (int) exec('tput cols');
            }
        } catch (Throwable $e) {
            
            
            static::$height = null;
            static::$width  = null;
            log_message('error', (string) $e);
        }
    }

    
    public static function showProgress($thisStep = 1, int $totalSteps = 10)
    {
        static $inProgress = false;

        
        if ($inProgress !== false && $inProgress <= $thisStep) {
            static::fwrite(STDOUT, "\033[1A");
        }
        $inProgress = $thisStep;

        if ($thisStep !== false) {
            
            $thisStep   = abs($thisStep);
            $totalSteps = $totalSteps < 1 ? 1 : $totalSteps;

            $percent = (int) (($thisStep / $totalSteps) * 100);
            $step    = (int) round($percent / 10);

            
            static::fwrite(STDOUT, "[\033[32m" . str_repeat('#', $step) . str_repeat('.', 10 - $step) . "\033[0m]");
            
            static::fwrite(STDOUT, sprintf(' %3d%% Complete', $percent) . PHP_EOL);
        } else {
            static::fwrite(STDOUT, "\007");
        }
    }

    
    public static function wrap(?string $string = null, int $max = 0, int $padLeft = 0): string
    {
        if ((string) $string === '') {
            return '';
        }

        if ($max === 0) {
            $max = self::getWidth();
        }

        if (self::getWidth() < $max) {
            $max = self::getWidth();
        }

        $max -= $padLeft;

        $lines = wordwrap($string, $max, PHP_EOL);

        if ($padLeft > 0) {
            $lines = explode(PHP_EOL, $lines);

            $first = true;

            array_walk($lines, static function (&$line) use ($padLeft, &$first): void {
                if (! $first) {
                    $line = str_repeat(' ', $padLeft) . $line;
                } else {
                    $first = false;
                }
            });

            $lines = implode(PHP_EOL, $lines);
        }

        return $lines;
    }

    
    
    

    
    protected static function parseCommandLine()
    {
        $args = $_SERVER['argv'] ?? [];
        array_shift($args); 
        $optionValue = false;

        foreach ($args as $i => $arg) {
            
            
            if (mb_strpos($arg, '-') !== 0) {
                if ($optionValue) {
                    
                    
                    $optionValue = false;
                } else {
                    
                    static::$segments[] = $arg;
                }

                continue;
            }

            $arg   = ltrim($arg, '-');
            $value = null;

            if (isset($args[$i + 1]) && mb_strpos($args[$i + 1], '-') !== 0) {
                $value       = $args[$i + 1];
                $optionValue = true;
            }

            static::$options[$arg] = $value;
        }
    }

    
    public static function getURI(): string
    {
        return implode('/', static::$segments);
    }

    
    public static function getSegment(int $index)
    {
        return static::$segments[$index - 1] ?? null;
    }

    
    public static function getSegments(): array
    {
        return static::$segments;
    }

    
    public static function getOption(string $name)
    {
        if (! array_key_exists($name, static::$options)) {
            return null;
        }

        
        
        $val = static::$options[$name] ?? true;

        return $val;
    }

    
    public static function getOptions(): array
    {
        return static::$options;
    }

    
    public static function getOptionString(bool $useLongOpts = false, bool $trim = false): string
    {
        if (static::$options === []) {
            return '';
        }

        $out = '';

        foreach (static::$options as $name => $value) {
            if ($useLongOpts && mb_strlen($name) > 1) {
                $out .= "--{$name} ";
            } else {
                $out .= "-{$name} ";
            }

            if ($value === null) {
                continue;
            }

            if (mb_strpos($value, ' ') !== false) {
                $out .= "\"{$value}\" ";
            } elseif ($value !== null) {
                $out .= "{$value} ";
            }
        }

        return $trim ? trim($out) : $out;
    }

    
    public static function table(array $tbody, array $thead = [])
    {
        
        $tableRows = [];

        
        if ($thead !== []) {
            $tableRows[] = array_values($thead);
        }

        foreach ($tbody as $tr) {
            $tableRows[] = array_values($tr);
        }

        
        $totalRows = count($tableRows);

        
        
        $allColsLengths = [];

        
        
        $maxColsLengths = [];

        
        for ($row = 0; $row < $totalRows; $row++) {
            $column = 0; 

            foreach ($tableRows[$row] as $col) {
                
                $allColsLengths[$row][$column] = static::strlen((string) $col);

                
                
                
                if (! isset($maxColsLengths[$column]) || $allColsLengths[$row][$column] > $maxColsLengths[$column]) {
                    $maxColsLengths[$column] = $allColsLengths[$row][$column];
                }

                
                $column++;
            }
        }

        
        
        for ($row = 0; $row < $totalRows; $row++) {
            $column = 0;

            foreach ($tableRows[$row] as $col) {
                $diff = $maxColsLengths[$column] - static::strlen((string) $col);

                if ($diff !== 0) {
                    $tableRows[$row][$column] .= str_repeat(' ', $diff);
                }

                $column++;
            }
        }

        $table = '';
        $cols  = '';

        
        for ($row = 0; $row < $totalRows; $row++) {
            
            if ($row === 0) {
                $cols = '+';

                foreach ($tableRows[$row] as $col) {
                    $cols .= str_repeat('-', static::strlen((string) $col) + 2) . '+';
                }
                $table .= $cols . PHP_EOL;
            }

            
            $table .= '| ' . implode(' | ', $tableRows[$row]) . ' |' . PHP_EOL;

            
            if (($row === 0 && $thead !== []) || ($row + 1 === $totalRows)) {
                $table .= $cols . PHP_EOL;
            }
        }

        static::write($table);
    }

    
    protected static function fwrite($handle, string $string)
    {
        static::$io->fwrite($handle, $string);
    }

    
    public static function reset(): void
    {
        static::$initialized = false;
        static::$segments    = [];
        static::$options     = [];
        static::$lastWrite   = null;
        static::$height      = null;
        static::$width       = null;
        static::$isColored   = static::hasColorSupport(STDOUT);

        static::resetInputOutput();
    }

    
    public static function resetLastWrite(): void
    {
        static::$lastWrite = null;
    }

    
    public static function setInputOutput(InputOutput $io): void
    {
        static::$io = $io;
    }

    
    public static function resetInputOutput(): void
    {
        static::$io = new InputOutput();
    }
}


CLI::init(); 
