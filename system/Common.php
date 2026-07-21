<?php

declare(strict_types=1);



use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Config\Factories;
use CodeIgniter\Cookie\Cookie;
use CodeIgniter\Cookie\CookieStore;
use CodeIgniter\Cookie\Exceptions\CookieException;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Debug\Timer;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Exceptions\RuntimeException;
use CodeIgniter\Files\Exceptions\FileNotFoundException;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Language\Language;
use CodeIgniter\Model;
use CodeIgniter\Session\Session;
use CodeIgniter\Test\TestLogger;
use Config\App;
use Config\Database;
use Config\DocTypes;
use Config\Logger;
use Config\Services;
use Config\View;
use Laminas\Escaper\Escaper;



if (! function_exists('app_timezone')) {
    
    function app_timezone(): string
    {
        $config = config(App::class);

        return $config->appTimezone;
    }
}

if (! function_exists('cache')) {
    
    function cache(?string $key = null)
    {
        $cache = service('cache');

        
        if ($key === null) {
            return $cache;
        }

        
        return $cache->get($key);
    }
}

if (! function_exists('clean_path')) {
    
    function clean_path(string $path): string
    {
        
        try {
            $path = realpath($path) ?: $path;
        } catch (ErrorException|ValueError) {
            $path = 'error file path: ' . urlencode($path);
        }

        return match (true) {
            str_starts_with($path, APPPATH)                             => 'APPPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(APPPATH)),
            str_starts_with($path, SYSTEMPATH)                          => 'SYSTEMPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(SYSTEMPATH)),
            str_starts_with($path, FCPATH)                              => 'FCPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(FCPATH)),
            defined('VENDORPATH') && str_starts_with($path, VENDORPATH) => 'VENDORPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(VENDORPATH)),
            str_starts_with($path, ROOTPATH)                            => 'ROOTPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(ROOTPATH)),
            default                                                     => $path,
        };
    }
}

if (! function_exists('command')) {
    
    function command(string $command)
    {
        $regexString = '([^\s]+?)(?:\s|(?<!\\\\)"|(?<!\\\\)\'|$)';
        $regexQuoted = '(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')';

        $args   = [];
        $length = strlen($command);
        $cursor = 0;

        
        while ($cursor < $length) {
            if (preg_match('/\s+/A', $command, $match, 0, $cursor)) {
                
            } elseif (preg_match('/' . $regexQuoted . '/A', $command, $match, 0, $cursor)) {
                $args[] = stripcslashes(substr($match[0], 1, strlen($match[0]) - 2));
            } elseif (preg_match('/' . $regexString . '/A', $command, $match, 0, $cursor)) {
                $args[] = stripcslashes($match[1]);
            } else {
                
                throw new InvalidArgumentException(sprintf(
                    'Unable to parse input near "... %s ...".',
                    substr($command, $cursor, 10),
                ));
                
            }

            $cursor += strlen($match[0]);
        }

        
        $params      = [];
        $command     = array_shift($args);
        $optionValue = false;

        foreach ($args as $i => $arg) {
            if (mb_strpos($arg, '-') !== 0) {
                if ($optionValue) {
                    
                    
                    $optionValue = false;
                } else {
                    
                    
                    $params[] = $arg;
                }

                continue;
            }

            $arg   = ltrim($arg, '-');
            $value = null;

            if (isset($args[$i + 1]) && mb_strpos($args[$i + 1], '-') !== 0) {
                $value       = $args[$i + 1];
                $optionValue = true;
            }

            $params[$arg] = $value;
        }

        ob_start();
        service('commands')->run($command, $params);

        return ob_get_clean();
    }
}

if (! function_exists('config')) {
    
    function config(string $name, bool $getShared = true)
    {
        if ($getShared) {
            return Factories::get('config', $name);
        }

        return Factories::config($name, ['getShared' => $getShared]);
    }
}

if (! function_exists('cookie')) {
    
    function cookie(string $name, string $value = '', array $options = []): Cookie
    {
        return new Cookie($name, $value, $options);
    }
}

if (! function_exists('cookies')) {
    
    function cookies(array $cookies = [], bool $getGlobal = true): CookieStore
    {
        if ($getGlobal) {
            return service('response')->getCookieStore();
        }

        return new CookieStore($cookies);
    }
}

if (! function_exists('csrf_token')) {
    
    function csrf_token(): string
    {
        return service('security')->getTokenName();
    }
}

if (! function_exists('csrf_header')) {
    
    function csrf_header(): string
    {
        return service('security')->getHeaderName();
    }
}

if (! function_exists('csrf_hash')) {
    
    function csrf_hash(): string
    {
        return service('security')->getHash();
    }
}

if (! function_exists('csrf_field')) {
    
    function csrf_field(?string $id = null): string
    {
        return '<input type="hidden"' . ($id !== null ? ' id="' . esc($id, 'attr') . '"' : '') . ' name="' . csrf_token() . '" value="' . csrf_hash() . '"' . _solidus() . '>';
    }
}

if (! function_exists('csrf_meta')) {
    
    function csrf_meta(?string $id = null): string
    {
        return '<meta' . ($id !== null ? ' id="' . esc($id, 'attr') . '"' : '') . ' name="' . csrf_header() . '" content="' . csrf_hash() . '"' . _solidus() . '>';
    }
}

if (! function_exists('csp_style_nonce')) {
    
    function csp_style_nonce(): string
    {
        $csp = service('csp');

        if (! $csp->enabled()) {
            return '';
        }

        return 'nonce="' . $csp->getStyleNonce() . '"';
    }
}

if (! function_exists('csp_script_nonce')) {
    
    function csp_script_nonce(): string
    {
        $csp = service('csp');

        if (! $csp->enabled()) {
            return '';
        }

        return 'nonce="' . $csp->getScriptNonce() . '"';
    }
}

if (! function_exists('db_connect')) {
    
    function db_connect($db = null, bool $getShared = true)
    {
        return Database::connect($db, $getShared);
    }
}

if (! function_exists('env')) {
    
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        
        if ($value === false) {
            return $default;
        }

        
        return match (strtolower($value)) {
            'true'  => true,
            'false' => false,
            'empty' => '',
            'null'  => null,
            default => $value,
        };
    }
}

if (! function_exists('esc')) {
    
    function esc($data, string $context = 'html', ?string $encoding = null)
    {
        $context = strtolower($context);

        
        
        
        if ($context === 'raw') {
            return $data;
        }

        if (is_array($data)) {
            foreach ($data as &$value) {
                $value = esc($value, $context);
            }
        }

        if (is_string($data)) {
            if (! in_array($context, ['html', 'js', 'css', 'url', 'attr'], true)) {
                throw new InvalidArgumentException('Invalid escape context provided.');
            }

            $method = $context === 'attr' ? 'escapeHtmlAttr' : 'escape' . ucfirst($context);

            static $escaper;
            if (! $escaper) {
                $escaper = new Escaper($encoding);
            }

            if ($encoding !== null && $escaper->getEncoding() !== $encoding) {
                $escaper = new Escaper($encoding);
            }

            $data = $escaper->{$method}($data);
        }

        return $data;
    }
}

if (! function_exists('force_https')) {
    
    function force_https(
        int $duration = 31_536_000,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null,
    ): void {
        $request ??= service('request');

        if (! $request instanceof IncomingRequest) {
            return;
        }

        $response ??= service('response');

        if ((ENVIRONMENT !== 'testing' && (is_cli() || $request->isSecure()))
            || $request->getServer('HTTPS') === 'test'
        ) {
            return; 
        }

        
        
        if (ENVIRONMENT !== 'testing' && session_status() === PHP_SESSION_ACTIVE) {
            service('session')->regenerate(); 
        }

        $uri = $request->getUri()->withScheme('https');

        
        $response->setHeader('Strict-Transport-Security', 'max-age=' . $duration)
            ->redirect((string) $uri)
            ->setStatusCode(307)
            ->setBody('')
            ->getCookieStore()
            ->clear();

        throw new RedirectException($response);
    }
}

if (! function_exists('function_usable')) {
    
    function function_usable(string $functionName): bool
    {
        static $_suhosin_func_blacklist;

        if (function_exists($functionName)) {
            if (! isset($_suhosin_func_blacklist)) {
                $_suhosin_func_blacklist = extension_loaded('suhosin') ? explode(',', trim(ini_get('suhosin.executor.func.blacklist'))) : [];
            }

            return ! in_array($functionName, $_suhosin_func_blacklist, true);
        }

        return false;
    }
}

if (! function_exists('helper')) {
    
    function helper($filenames): void
    {
        static $loaded = [];

        $loader = service('locator');

        if (! is_array($filenames)) {
            $filenames = [$filenames];
        }

        
        $includes = [];

        foreach ($filenames as $filename) {
            
            
            $systemHelper  = '';
            $appHelper     = '';
            $localIncludes = [];

            if (! str_contains($filename, '_helper')) {
                $filename .= '_helper';
            }

            
            if (in_array($filename, $loaded, true)) {
                continue;
            }

            
            
            if (str_contains($filename, '\\')) {
                $path = $loader->locateFile($filename, 'Helpers');

                if ($path === false) {
                    throw FileNotFoundException::forFileNotFound($filename);
                }

                $includes[] = $path;
                $loaded[]   = $filename;
            } else {
                
                $paths = $loader->search('Helpers/' . $filename);

                foreach ($paths as $path) {
                    if (str_starts_with($path, APPPATH . 'Helpers' . DIRECTORY_SEPARATOR)) {
                        $appHelper = $path;
                    } elseif (str_starts_with($path, SYSTEMPATH . 'Helpers' . DIRECTORY_SEPARATOR)) {
                        $systemHelper = $path;
                    } else {
                        $localIncludes[] = $path;
                        $loaded[]        = $filename;
                    }
                }

                
                if ($appHelper !== '') {
                    $includes[] = $appHelper;
                    $loaded[]   = $filename;
                }

                
                $includes = [...$includes, ...$localIncludes];

                
                if ($systemHelper !== '') {
                    $includes[] = $systemHelper;
                    $loaded[]   = $filename;
                }
            }
        }

        
        foreach ($includes as $path) {
            include_once $path;
        }
    }
}

if (! function_exists('is_cli')) {
    
    function is_cli(): bool
    {
        if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            return true;
        }

        
        
        return ! isset($_SERVER['REMOTE_ADDR']) && ! isset($_SERVER['REQUEST_METHOD']);
    }
}

if (! function_exists('is_really_writable')) {
    
    function is_really_writable(string $file): bool
    {
        
        if (! is_windows()) {
            return is_writable($file);
        }

        
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . bin2hex(random_bytes(16));
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);

            return true;
        }

        if (! is_file($file) || ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }

        fclose($fp);

        return true;
    }
}

if (! function_exists('is_windows')) {
    
    function is_windows(?bool $mock = null): bool
    {
        static $mocked;

        if (func_num_args() === 1) {
            $mocked = $mock;
        }

        return $mocked ?? DIRECTORY_SEPARATOR === '\\';
    }
}

if (! function_exists('lang')) {
    
    function lang(string $line, array $args = [], ?string $locale = null)
    {
        
        $language = service('language');

        
        $activeLocale = $language->getLocale();

        if ((string) $locale !== '' && $locale !== $activeLocale) {
            $language->setLocale($locale);
        }

        $lines = $language->getLine($line, $args);

        if ((string) $locale !== '' && $locale !== $activeLocale) {
            
            $language->setLocale($activeLocale);
        }

        return $lines;
    }
}

if (! function_exists('log_message')) {
    
    function log_message(string $level, string $message, array $context = []): void
    {
        
        
        
        if (ENVIRONMENT === 'testing') {
            $logger = new TestLogger(new Logger());

            $logger->log($level, $message, $context);

            return;
        }

        service('logger')->log($level, $message, $context); 
    }
}

if (! function_exists('model')) {
    
    function model(string $name, bool $getShared = true, ?ConnectionInterface &$conn = null)
    {
        return Factories::models($name, ['getShared' => $getShared], $conn);
    }
}

if (! function_exists('old')) {
    
    function old(string $key, $default = null, $escape = 'html')
    {
        
        if (session_status() === PHP_SESSION_NONE && ENVIRONMENT !== 'testing') {
            session(); 
        }

        $request = service('request');

        $value = $request->getOldInput($key);

        
        
        if ($value === null) {
            return $default;
        }

        return $escape === false ? $value : esc($value, $escape);
    }
}

if (! function_exists('redirect')) {
    
    function redirect(?string $route = null): RedirectResponse
    {
        $response = service('redirectresponse');

        if ((string) $route !== '') {
            return $response->route($route);
        }

        return $response;
    }
}

if (! function_exists('_solidus')) {
    
    function _solidus(?DocTypes $docTypesConfig = null): string
    {
        static $docTypes = null;

        if ($docTypesConfig instanceof DocTypes) {
            $docTypes = $docTypesConfig;
        }

        $docTypes ??= new DocTypes();

        if ($docTypes->html5 ?? false) {
            return '';
        }

        return ' /';
    }
}

if (! function_exists('remove_invisible_characters')) {
    
    function remove_invisible_characters(string $str, bool $urlEncoded = true): string
    {
        $nonDisplayables = [];

        
        
        if ($urlEncoded) {
            $nonDisplayables[] = '/%0[0-8bcef]/';  
            $nonDisplayables[] = '/%1[0-9a-f]/';   
        }

        $nonDisplayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';   

        do {
            $str = preg_replace($nonDisplayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }
}

if (! function_exists('render_backtrace')) {
    
    function render_backtrace(array $backtrace): string
    {
        $backtraces = [];

        foreach ($backtrace as $index => $trace) {
            $frame = $trace + ['file' => '[internal function]', 'line' => 0, 'class' => '', 'type' => '', 'args' => []];

            if ($frame['file'] !== '[internal function]') {
                $frame['file'] = sprintf('%s(%s)', $frame['file'], $frame['line']);
            }

            unset($frame['line']);
            $idx = $index;
            $idx = str_pad((string) ++$idx, 2, ' ', STR_PAD_LEFT);

            $args = implode(', ', array_map(static fn ($value): string => match (true) {
                is_object($value)   => sprintf('Object(%s)', $value::class),
                is_array($value)    => $value !== [] ? '[...]' : '[]',
                $value === null     => 'null',
                is_resource($value) => sprintf('resource (%s)', get_resource_type($value)),
                default             => var_export($value, true),
            }, $frame['args']));

            $backtraces[] = sprintf(
                '%s %s: %s%s%s(%s)',
                $idx,
                clean_path($frame['file']),
                $frame['class'],
                $frame['type'],
                $frame['function'],
                $args,
            );
        }

        return implode("\n", $backtraces);
    }
}

if (! function_exists('request')) {
    
    function request()
    {
        return service('request');
    }
}

if (! function_exists('response')) {
    
    function response(): ResponseInterface
    {
        return service('response');
    }
}

if (! function_exists('route_to')) {
    
    function route_to(string $method, ...$params)
    {
        return service('routes')->reverseRoute($method, ...$params);
    }
}

if (! function_exists('session')) {
    
    function session(?string $val = null)
    {
        $session = service('session');

        
        if (is_string($val)) {
            return $session->get($val);
        }

        return $session;
    }
}

if (! function_exists('service')) {
    
    function service(string $name, ...$params): ?object
    {
        if ($params === []) {
            return Services::get($name);
        }

        return Services::$name(...$params);
    }
}

if (! function_exists('single_service')) {
    
    function single_service(string $name, ...$params): ?object
    {
        $service = Services::serviceExists($name);

        if ($service === null) {
            
            return null;
        }

        $method = new ReflectionMethod($service, $name);
        $count  = $method->getNumberOfParameters();
        $mParam = $method->getParameters();

        if ($count === 1) {
            
            
            return $service::$name(false);
        }

        
        for ($startIndex = count($params); $startIndex <= $count - 2; $startIndex++) {
            $params[$startIndex] = $mParam[$startIndex]->getDefaultValue();
        }

        
        $params[$count - 1] = false;

        return $service::$name(...$params);
    }
}

if (! function_exists('slash_item')) {
    
    
    
    function slash_item(string $item): ?string
    {
        $config = config(App::class);

        if (! property_exists($config, $item)) {
            return null;
        }

        $configItem = $config->{$item};

        if (! is_scalar($configItem)) {
            throw new RuntimeException(sprintf(
                'Cannot convert "%s::$%s" of type "%s" to type "string".',
                App::class,
                $item,
                gettype($configItem),
            ));
        }

        $configItem = trim((string) $configItem);

        if ($configItem === '') {
            return $configItem;
        }

        return rtrim($configItem, '/') . '/';
    }
}

if (! function_exists('stringify_attributes')) {
    
    function stringify_attributes($attributes, bool $js = false): string
    {
        $atts = '';

        if (in_array($attributes, ['', [], null], true)) {
            return $atts;
        }

        if (is_string($attributes)) {
            return ' ' . $attributes;
        }

        $attributes = (array) $attributes;

        foreach ($attributes as $key => $val) {
            $atts .= ($js) ? $key . '=' . esc($val, 'js') . ',' : ' ' . $key . '="' . esc($val) . '"';
        }

        return rtrim($atts, ',');
    }
}

if (! function_exists('timer')) {
    
    function timer(?string $name = null, ?callable $callable = null)
    {
        $timer = service('timer');

        if ($name === null) {
            return $timer;
        }

        if ($callable !== null) {
            return $timer->record($name, $callable);
        }

        if ($timer->has($name)) {
            return $timer->stop($name);
        }

        return $timer->start($name);
    }
}

if (! function_exists('view')) {
    
    function view(string $name, array $data = [], array $options = []): string
    {
        $renderer = service('renderer');

        $config   = config(View::class);
        $saveData = $config->saveData;

        if (array_key_exists('saveData', $options)) {
            $saveData = (bool) $options['saveData'];
            unset($options['saveData']);
        }

        return $renderer->setData($data, 'raw')->render($name, $options, $saveData);
    }
}

if (! function_exists('view_cell')) {
    
    function view_cell(string $library, $params = null, int $ttl = 0, ?string $cacheName = null): string
    {
        return service('viewcell')
            ->render($library, $params, $ttl, $cacheName);
    }
}


if (! function_exists('class_basename')) {
    
    function class_basename($class)
    {
        $class = is_object($class) ? $class::class : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (! function_exists('class_uses_recursive')) {
    
    function class_uses_recursive($class)
    {
        if (is_object($class)) {
            $class = $class::class;
        }

        $results = [];

        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (! function_exists('trait_uses_recursive')) {
    
    function trait_uses_recursive($trait)
    {
        $traits = class_uses($trait) ?: [];

        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}
