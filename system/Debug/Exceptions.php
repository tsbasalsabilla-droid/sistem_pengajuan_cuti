<?php

declare(strict_types=1);



namespace CodeIgniter\Debug;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Exceptions\HasExitCodeInterface;
use CodeIgniter\Exceptions\HTTPExceptionInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Exceptions as ExceptionsConfig;
use Config\Paths;
use ErrorException;
use Psr\Log\LogLevel;
use Throwable;


class Exceptions
{
    use ResponseTrait;

    
    public $ob_level;

    
    protected $viewPath;

    
    protected $config;

    
    protected $request;

    
    protected $response;

    private ?Throwable $exceptionCaughtByExceptionHandler = null;

    public function __construct(ExceptionsConfig $config)
    {
        
        $this->ob_level = ob_get_level();
        $this->viewPath = rtrim($config->errorViewPath, '\\/ ') . DIRECTORY_SEPARATOR;

        $this->config = $config;
    }

    
    public function initialize()
    {
        set_exception_handler($this->exceptionHandler(...));
        set_error_handler($this->errorHandler(...));
        register_shutdown_function([$this, 'shutdownHandler']);
    }

    
    public function exceptionHandler(Throwable $exception)
    {
        $this->exceptionCaughtByExceptionHandler = $exception;

        [$statusCode, $exitCode] = $this->determineCodes($exception);

        $this->request = service('request');

        if ($this->config->log === true && ! in_array($statusCode, $this->config->ignoreCodes, true)) {
            $uri       = $this->request->getPath() === '' ? '/' : $this->request->getPath();
            $routeInfo = '[Method: ' . $this->request->getMethod() . ', Route: ' . $uri . ']';

            log_message('critical', $exception::class . ": {message}\n{routeInfo}\nin {exFile} on line {exLine}.\n{trace}", [
                'message'   => $exception->getMessage(),
                'routeInfo' => $routeInfo,
                'exFile'    => clean_path($exception->getFile()), 
                'exLine'    => $exception->getLine(), 
                'trace'     => render_backtrace($exception->getTrace()),
            ]);

            
            $last = $exception;

            while ($prevException = $last->getPrevious()) {
                $last = $prevException;

                log_message('critical', '[Caused by] ' . $prevException::class . ": {message}\nin {exFile} on line {exLine}.\n{trace}", [
                    'message' => $prevException->getMessage(),
                    'exFile'  => clean_path($prevException->getFile()), 
                    'exLine'  => $prevException->getLine(), 
                    'trace'   => render_backtrace($prevException->getTrace()),
                ]);
            }
        }

        $this->response = service('response');

        if (method_exists($this->config, 'handler')) {
            
            $handler = $this->config->handler($statusCode, $exception);
            $handler->handle(
                $exception,
                $this->request,
                $this->response,
                $statusCode,
                $exitCode,
            );

            return;
        }

        
        if (! is_cli()) {
            try {
                $this->response->setStatusCode($statusCode);
            } catch (HTTPException) {
                
                $statusCode = 500;
                $this->response->setStatusCode($statusCode);
            }

            if (! headers_sent()) {
                header(sprintf('HTTP/%s %s %s', $this->request->getProtocolVersion(), $this->response->getStatusCode(), $this->response->getReasonPhrase()), true, $statusCode);
            }

            if (! str_contains($this->request->getHeaderLine('accept'), 'text/html')) {
                $this->respond(ENVIRONMENT === 'development' ? $this->collectVars($exception, $statusCode) : '', $statusCode)->send();

                exit($exitCode);
            }
        }

        $this->render($exception, $statusCode);

        exit($exitCode);
    }

    
    public function errorHandler(int $severity, string $message, ?string $file = null, ?int $line = null)
    {
        if ($this->isDeprecationError($severity)) {
            if ($this->isSessionSidDeprecationError($message, $file, $line)) {
                return true;
            }

            if (! $this->config->logDeprecations || (bool) env('CODEIGNITER_SCREAM_DEPRECATIONS')) {
                throw new ErrorException($message, 0, $severity, $file, $line);
            }

            return $this->handleDeprecationError($message, $file, $line);
        }

        if ((error_reporting() & $severity) !== 0) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }

        return false; 
    }

    
    private function isSessionSidDeprecationError(string $message, ?string $file = null, ?int $line = null): bool
    {
        if (
            PHP_VERSION_ID >= 80400
            && str_contains($message, 'session.sid_')
        ) {
            log_message(
                LogLevel::WARNING,
                '[DEPRECATED] {message} in {errFile} on line {errLine}.',
                [
                    'message' => $message,
                    'errFile' => clean_path($file ?? ''),
                    'errLine' => $line ?? 0,
                ],
            );

            return true;
        }

        return false;
    }

    
    public function shutdownHandler()
    {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        ['type' => $type, 'message' => $message, 'file' => $file, 'line' => $line] = $error;

        if ($this->exceptionCaughtByExceptionHandler instanceof Throwable) {
            $message .= "\n【Previous Exception】\n"
                . $this->exceptionCaughtByExceptionHandler::class . "\n"
                . $this->exceptionCaughtByExceptionHandler->getMessage() . "\n"
                . $this->exceptionCaughtByExceptionHandler->getTraceAsString();
        }

        if (in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            $this->exceptionHandler(new ErrorException($message, 0, $type, $file, $line));
        }
    }

    
    protected function determineView(Throwable $exception, string $templatePath): string
    {
        
        $view         = 'production.php';
        $templatePath = rtrim($templatePath, '\\/ ') . DIRECTORY_SEPARATOR;

        if (
            in_array(
                strtolower(ini_get('display_errors')),
                ['1', 'true', 'on', 'yes'],
                true,
            )
        ) {
            $view = 'error_exception.php';
        }

        
        if ($exception instanceof PageNotFoundException) {
            return 'error_404.php';
        }

        
        if (is_file($templatePath . 'error_' . $exception->getCode() . '.php')) {
            return 'error_' . $exception->getCode() . '.php';
        }

        return $view;
    }

    
    protected function render(Throwable $exception, int $statusCode)
    {
        
        $path    = $this->viewPath;
        $altPath = rtrim((new Paths())->viewDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR;

        $path    .= (is_cli() ? 'cli' : 'html') . DIRECTORY_SEPARATOR;
        $altPath .= (is_cli() ? 'cli' : 'html') . DIRECTORY_SEPARATOR;

        
        $view    = $this->determineView($exception, $path);
        $altView = $this->determineView($exception, $altPath);

        
        if (is_file($path . $view)) {
            $viewFile = $path . $view;
        } elseif (is_file($altPath . $altView)) {
            $viewFile = $altPath . $altView;
        }

        if (! isset($viewFile)) {
            echo 'The error view files were not found. Cannot render exception trace.';

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

    
    protected function maskSensitiveData($trace, array $keysToMask, string $path = '')
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

    
    protected function determineCodes(Throwable $exception): array
    {
        $statusCode = 500;
        $exitStatus = EXIT_ERROR;

        if ($exception instanceof HTTPExceptionInterface) {
            $statusCode = $exception->getCode();
        }

        if ($exception instanceof HasExitCodeInterface) {
            $exitStatus = $exception->getExitCode();
        }

        return [$statusCode, $exitStatus];
    }

    private function isDeprecationError(int $error): bool
    {
        $deprecations = E_DEPRECATED | E_USER_DEPRECATED;

        return ($error & $deprecations) !== 0;
    }

    
    private function handleDeprecationError(string $message, ?string $file = null, ?int $line = null): bool
    {
        
        $trace = array_slice(debug_backtrace(), 2);

        log_message(
            $this->config->deprecationLogLevel,
            "[DEPRECATED] {message} in {errFile} on line {errLine}.\n{trace}",
            [
                'message' => $message,
                'errFile' => clean_path($file ?? ''),
                'errLine' => $line ?? 0,
                'trace'   => render_backtrace($trace),
            ],
        );

        return true;
    }

    
    
    

    
    public static function cleanPath(string $file): string
    {
        return match (true) {
            str_starts_with($file, APPPATH)                             => 'APPPATH' . DIRECTORY_SEPARATOR . substr($file, strlen(APPPATH)),
            str_starts_with($file, SYSTEMPATH)                          => 'SYSTEMPATH' . DIRECTORY_SEPARATOR . substr($file, strlen(SYSTEMPATH)),
            str_starts_with($file, FCPATH)                              => 'FCPATH' . DIRECTORY_SEPARATOR . substr($file, strlen(FCPATH)),
            defined('VENDORPATH') && str_starts_with($file, VENDORPATH) => 'VENDORPATH' . DIRECTORY_SEPARATOR . substr($file, strlen(VENDORPATH)),
            default                                                     => $file,
        };
    }

    
    public static function describeMemory(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . 'B';
        }

        if ($bytes < 1_048_576) {
            return round($bytes / 1024, 2) . 'KB';
        }

        return round($bytes / 1_048_576, 2) . 'MB';
    }

    
    public static function highlightFile(string $file, int $lineNumber, int $lines = 15)
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
        $source = str_replace('<br />', "\n", $source[1]);
        $source = explode("\n", str_replace("\r\n", "\n", $source));

        
        $start = max($lineNumber - (int) round($lines / 2), 0);

        
        $source = array_splice($source, $start, $lines, true);

        
        $format = '% ' . strlen((string) ($start + $lines)) . 'd';

        $out = '';
        
        
        
        
        $spans = 1;

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
            }
        }

        if ($spans > 0) {
            $out .= str_repeat('</span>', $spans);
        }

        return '<pre><code>' . $out . '</code></pre>';
    }
}
