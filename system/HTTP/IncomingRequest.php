<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Files\FileCollection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\App;
use Config\Services;
use Locale;
use stdClass;


class IncomingRequest extends Request
{
    
    protected $uri;

    
    protected $path;

    
    protected $files;

    
    protected $negotiator;

    
    protected $defaultLocale;

    
    protected $locale;

    
    protected $validLocales = [];

    
    protected $oldInput = [];

    
    protected $userAgent;

    
    public function __construct($config, ?URI $uri = null, $body = 'php://input', ?UserAgent $userAgent = null)
    {
        if (! $uri instanceof URI || ! $userAgent instanceof UserAgent) {
            throw new InvalidArgumentException('You must supply the parameters: uri, userAgent.');
        }

        $this->populateHeaders();

        if (
            $body === 'php://input'
            
            
            && ! str_contains($this->getHeaderLine('Content-Type'), 'multipart/form-data')
            && (int) $this->getHeaderLine('Content-Length') <= $this->getPostMaxSize()
        ) {
            
            $body = file_get_contents('php://input');
        }

        
        if ($body === false || $body === '') {
            $body = null;
        }

        $this->uri          = $uri;
        $this->body         = $body;
        $this->userAgent    = $userAgent;
        $this->validLocales = $config->supportedLocales;

        parent::__construct($config);

        if ($uri instanceof SiteURI) {
            $this->setPath($uri->getRoutePath());
        } else {
            $this->setPath($uri->getPath());
        }

        $this->detectLocale($config);
    }

    private function getPostMaxSize(): int
    {
        $postMaxSize = ini_get('post_max_size');

        return match (strtoupper(substr($postMaxSize, -1))) {
            'G'     => (int) str_replace('G', '', $postMaxSize) * 1024 ** 3,
            'M'     => (int) str_replace('M', '', $postMaxSize) * 1024 ** 2,
            'K'     => (int) str_replace('K', '', $postMaxSize) * 1024,
            default => (int) $postMaxSize,
        };
    }

    
    public function detectLocale($config)
    {
        $this->locale = $this->defaultLocale = $config->defaultLocale;

        if (! $config->negotiateLocale) {
            return;
        }

        $this->setLocale($this->negotiate('language', $config->supportedLocales));
    }

    
    public function negotiate(string $type, array $supported, bool $strictMatch = false): string
    {
        if ($this->negotiator === null) {
            $this->negotiator = Services::negotiator($this, true);
        }

        return match (strtolower($type)) {
            'media'    => $this->negotiator->media($supported, $strictMatch),
            'charset'  => $this->negotiator->charset($supported),
            'encoding' => $this->negotiator->encoding($supported),
            'language' => $this->negotiator->language($supported),
            default    => throw HTTPException::forInvalidNegotiationType($type),
        };
    }

    
    public function is(string $type): bool
    {
        $valueUpper = strtoupper($type);

        $httpMethods = Method::all();

        if (in_array($valueUpper, $httpMethods, true)) {
            return $this->getMethod() === $valueUpper;
        }

        if ($valueUpper === 'JSON') {
            return str_contains($this->getHeaderLine('Content-Type'), 'application/json');
        }

        if ($valueUpper === 'AJAX') {
            return $this->isAJAX();
        }

        throw new InvalidArgumentException('Unknown type: ' . $type);
    }

    
    public function isCLI(): bool
    {
        return false;
    }

    
    public function isAJAX(): bool
    {
        return $this->hasHeader('X-Requested-With')
            && strtolower($this->header('X-Requested-With')->getValue()) === 'xmlhttprequest';
    }

    
    public function isSecure(): bool
    {
        $https = service('superglobals')->server('HTTPS');

        if ($https !== null && strtolower($https) !== 'off') {
            return true;
        }

        if ($this->hasHeader('X-Forwarded-Proto') && $this->header('X-Forwarded-Proto')->getValue() === 'https') {
            return true;
        }

        return $this->hasHeader('Front-End-Https') && ! empty($this->header('Front-End-Https')->getValue()) && strtolower($this->header('Front-End-Https')->getValue()) !== 'off';
    }

    
    private function setPath(string $path)
    {
        $this->path = $path;

        return $this;
    }

    
    public function getPath(): string
    {
        return $this->path;
    }

    
    public function setLocale(string $locale)
    {
        
        
        if (! in_array($locale, $this->validLocales, true)) {
            $locale = $this->defaultLocale;
        }

        $this->locale = $locale;
        Locale::setDefault($locale);

        return $this;
    }

    
    public function setValidLocales(array $locales)
    {
        $this->validLocales = $locales;

        return $this;
    }

    
    public function getLocale(): string
    {
        return $this->locale;
    }

    
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    
    public function getVar($index = null, $filter = null, $flags = null)
    {
        if (
            str_contains($this->getHeaderLine('Content-Type'), 'application/json')
            && $this->body !== null
        ) {
            return $this->getJsonVar($index, false, $filter, $flags);
        }

        return $this->fetchGlobal('request', $index, $filter, $flags);
    }

    
    public function getJSON(bool $assoc = false, int $depth = 512, int $options = 0)
    {
        if ($this->body === null) {
            return null;
        }

        $result = json_decode($this->body, $assoc, $depth, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw HTTPException::forInvalidJSON(json_last_error_msg());
        }

        return $result;
    }

    
    public function getJsonVar($index = null, bool $assoc = false, ?int $filter = null, $flags = null)
    {
        helper('array');

        $data = $this->getJSON(true);
        if (! is_array($data)) {
            return null;
        }

        if (is_string($index)) {
            $data = dot_array_search($index, $data);
        } elseif (is_array($index)) {
            $result = [];

            foreach ($index as $key) {
                $result[$key] = dot_array_search($key, $data);
            }

            [$data, $result] = [$result, null];
        }

        if ($data === null) {
            return null;
        }

        $filter ??= FILTER_UNSAFE_RAW;
        $flags = is_array($flags) ? $flags : (is_numeric($flags) ? (int) $flags : 0);

        if ($filter !== FILTER_UNSAFE_RAW
            || (
                (is_numeric($flags) && $flags !== 0)
                || is_array($flags) && $flags !== []
            )
        ) {
            if (is_array($data)) {
                
                array_walk_recursive($data, static function (&$val) use ($filter, $flags): void {
                    $valType = gettype($val);
                    $val     = filter_var($val, $filter, $flags);

                    if (in_array($valType, ['int', 'integer', 'float', 'double', 'bool', 'boolean'], true) && $val !== false) {
                        settype($val, $valType);
                    }
                });
            } else {
                $dataType = gettype($data);
                $data     = filter_var($data, $filter, $flags);

                if (in_array($dataType, ['int', 'integer', 'float', 'double', 'bool', 'boolean'], true) && $data !== false) {
                    settype($data, $dataType);
                }
            }
        }

        if (! $assoc) {
            if (is_array($index)) {
                foreach ($data as &$val) {
                    $val = is_array($val) ? json_decode(json_encode($val)) : $val;
                }

                return $data;
            }

            return json_decode(json_encode($data));
        }

        return $data;
    }

    
    public function getRawInput()
    {
        parse_str($this->body ?? '', $output);

        return $output;
    }

    
    public function getRawInputVar($index = null, ?int $filter = null, $flags = null)
    {
        helper('array');

        parse_str($this->body ?? '', $output);

        if (is_string($index)) {
            $output = dot_array_search($index, $output);
        } elseif (is_array($index)) {
            $data = [];

            foreach ($index as $key) {
                $data[$key] = dot_array_search($key, $output);
            }

            [$output, $data] = [$data, null];
        }

        $filter ??= FILTER_UNSAFE_RAW;
        $flags = is_array($flags) ? $flags : (is_numeric($flags) ? (int) $flags : 0);

        if (is_array($output)
            && (
                $filter !== FILTER_UNSAFE_RAW
                || (
                    (is_numeric($flags) && $flags !== 0)
                    || is_array($flags) && $flags !== []
                )
            )
        ) {
            
            array_walk_recursive($output, static function (&$val) use ($filter, $flags): void {
                $val = filter_var($val, $filter, $flags);
            });

            return $output;
        }

        if (is_string($output)) {
            return filter_var($output, $filter, $flags);
        }

        return $output;
    }

    
    public function getGet($index = null, $filter = null, $flags = null)
    {
        return $this->fetchGlobal('get', $index, $filter, $flags);
    }

    
    public function getPost($index = null, $filter = null, $flags = null)
    {
        return $this->fetchGlobal('post', $index, $filter, $flags);
    }

    
    public function getPostGet($index = null, $filter = null, $flags = null)
    {
        if ($index === null) {
            return array_merge($this->getGet($index, $filter, $flags), $this->getPost($index, $filter, $flags));
        }

        
        
        
        return service('superglobals')->post($index) !== null
            ? $this->getPost($index, $filter, $flags)
            : (service('superglobals')->get($index) !== null ? $this->getGet($index, $filter, $flags) : $this->getPost($index, $filter, $flags));
    }

    
    public function getGetPost($index = null, $filter = null, $flags = null)
    {
        if ($index === null) {
            return array_merge($this->getPost($index, $filter, $flags), $this->getGet($index, $filter, $flags));
        }

        
        
        
        return service('superglobals')->get($index) !== null
            ? $this->getGet($index, $filter, $flags)
            : (service('superglobals')->post($index) !== null ? $this->getPost($index, $filter, $flags) : $this->getGet($index, $filter, $flags));
    }

    
    public function getCookie($index = null, $filter = null, $flags = null)
    {
        return $this->fetchGlobal('cookie', $index, $filter, $flags);
    }

    
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    
    public function getOldInput(string $key)
    {
        
        if (! isset($_SESSION)) {
            return null;
        }

        
        $old = session('_ci_old_input');

        
        if ($old === null) {
            return null;
        }

        
        if (isset($old['post'][$key])) {
            return $old['post'][$key];
        }

        
        if (isset($old['get'][$key])) {
            return $old['get'][$key];
        }

        helper('array');

        
        if (isset($old['post'])) {
            $value = dot_array_search($key, $old['post']);
            if ($value !== null) {
                return $value;
            }
        }

        
        if (isset($old['get'])) {
            $value = dot_array_search($key, $old['get']);
            if ($value !== null) {
                return $value;
            }
        }

        
        return null;
    }

    
    public function getFiles(): array
    {
        if ($this->files === null) {
            $this->files = new FileCollection();
        }

        return $this->files->all(); 
    }

    
    public function getFileMultiple(string $fileID)
    {
        if ($this->files === null) {
            $this->files = new FileCollection();
        }

        return $this->files->getFileMultiple($fileID);
    }

    
    public function getFile(string $fileID)
    {
        if ($this->files === null) {
            $this->files = new FileCollection();
        }

        return $this->files->getFile($fileID);
    }
}
