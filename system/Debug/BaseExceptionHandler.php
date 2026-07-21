<?php

declare(strict_types=1);



namespace CodeIgniter\Debug;

use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Exceptions as ExceptionsConfig;
use Throwable;


abstract class BaseExceptionHandler
{
    
    protected ExceptionsConfig $config;

    
    protected int $obLevel;

    
    protected ?string $viewPath = null;

    public function __construct(ExceptionsConfig $config)
    {
        $this->config = $config;

        $this->obLevel = ob_get_level();

        if ($this->viewPath === null) {
            $this->viewPath = rtrim($this->config->errorViewPath, '\\/ ') . DIRECTORY_SEPARATOR;
        }
    }

    
    abstract public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    );

    
    protected function collectVars(Throwable $exception, int $statusCode): array
    {
        
        $firstException = $exception;

        while ($prevException = $firstException->getPrevious()) {
            $firstException = $prevException;
        }

        $trace = $firstException->getTrace();

        if ($this->config->sensitiveDataInTrace !== []) {
            $trace = $this->maskSensitiveData($trace, $this->config->sensitiveDataInTrace);
        }

        return [
            'title'   => $exception::class,
            'type'    => $exception::class,
            'code'    => $statusCode,
            'message' => $exception->getMessage(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'trace'   => $trace,
        ];
    }

    
    protected function maskSensitiveData(array $trace, array $keysToMask, string $path = ''): array
    {
        foreach ($trace as $i => $line) {
            $trace[$i]['args'] = $this->maskData($line['args'], $keysToMask);
        }

        return $trace;
    }

    
    private function maskData($args, array $keysToMask, string $path = '')
    {
        foreach ($keysToMask as $keyToMask) {
            $explode = explode('/', $keyToMask);
            $index   = end($explode);

            if (str_starts_with(strrev($path . '/' . $index), strrev($keyToMask))) {
                if (is_array($args) && array_key_exists($index, $args)) {
                    $args[$index] = '******************';
                } elseif (
                    is_object($args) && property_exists($args, $index)
                    && isset($args->{$index}) && is_scalar($args->{$index})
                ) {
                    $args->{$index} = '******************';
                }
            }
        }

        if (is_array($args)) {
            foreach ($args as $pathKey => $subarray) {
                $args[$pathKey] = $this->maskData($subarray, $keysToMask, $path . '/' . $pathKey);
            }
        } elseif (is_object($args)) {
            foreach ($args as $pathKey => $subarray) {
                $args->{$pathKey} = $this->maskData($subarray, $keysToMask, $path . '/' . $pathKey);
            }
        }

        return $args;
    }

    
    protected static function describeMemory(int $bytes): string
    {
        helper('number');

        return number_to_size($bytes, 2);
    }

    
    protected static function highlightFile(string $file, int $lineNumber, int $lines = 15)
    {
        if ($file === '' || ! is_readable($file)) {
            return false;
        }

        
        if (function_exists('ini_set')) {
            ini_set('highlight.comment', '#767a7e; font-style: italic');
            ini_set('highlight.default', '#c7c7c7');
            ini_set('highlight.html', '#06B');
            ini_set('highlight.keyword', '#f1ce61;');
            ini_set('highlight.string', '#869d6a');
        }

        try {
            $source = file_get_contents($file);
        } catch (Throwable) {
            return false;
        }

        $source = str_replace(["\r\n", "\r"], "\n", $source);
        $source = explode("\n", highlight_string($source, true));

        if (PHP_VERSION_ID < 80300) {
            $source = str_replace('<br />', "\n", $source[1]);
            $source = explode("\n", str_replace("\r\n", "\n", $source));
        } else {
            
            
            $source = str_replace(['<pre><code>', '</code></pre>'], '', $source);
        }

        
        $start = max($lineNumber - (int) round($lines / 2), 0);

        
        $source = array_splice($source, $start, $lines, true);

        
        $format = '% ' . strlen((string) ($start + $lines)) . 'd';

        $out = '';
        
        
        
        
        $spans = 0;

        foreach ($source as $n => $row) {
            $spans += substr_count($row, '<span') - substr_count($row, '</span');
            $row = str_replace(["\r", "\n"], ['', ''], $row);

            if (($n + $start + 1) === $lineNumber) {
                preg_match_all('#<[^>]+>#', $row, $tags);

                $out .= sprintf(
                    "<span class='line highlight'><span class='number'>{$format}</span> %s\n</span>%s",
                    $n + $start + 1,
                    strip_tags($row),
                    implode('', $tags[0]),
                );
            } else {
                $out .= sprintf('<span class="line"><span class="number">' . $format . '</span> %s', $n + $start + 1, $row) . "\n";
                
                
                $spans++;
            }
        }

        if ($spans > 0) {
            $out .= str_repeat('</span>', $spans);
        }

        return '<pre><code>' . $out . '</code></pre>';
    }

    
    protected function render(Throwable $exception, int $statusCode, $viewFile = null): void
    {
        if ($viewFile === null) {
            echo 'The error view file was not specified. Cannot display error view.';

            exit(1);
        }

        if (! is_file($viewFile)) {
            echo 'The error view file "' . $viewFile . '" was not found. Cannot display error view.';

            exit(1);
        }

        echo (function () use ($exception, $statusCode, $viewFile): string {
            $vars = $this->collectVars($exception, $statusCode);
            extract($vars, EXTR_SKIP);

            
            ob_start();
            include $viewFile;

            return ob_get_clean();
        })();
    }
}
