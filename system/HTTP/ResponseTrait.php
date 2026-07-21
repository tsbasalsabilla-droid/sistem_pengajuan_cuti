<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\Cookie\Cookie;
use CodeIgniter\Cookie\CookieStore;
use CodeIgniter\Cookie\Exceptions\CookieException;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Pager\PagerInterface;
use CodeIgniter\Security\Exceptions\SecurityException;
use Config\Cookie as CookieConfig;
use DateTime;
use DateTimeZone;


trait ResponseTrait
{
    
    protected $CSP;

    
    protected $cookieStore;

    
    protected $bodyFormat = 'html';

    
    public function setStatusCode(int $code, string $reason = '')
    {
        
        if ($code < 100 || $code > 599) {
            throw HTTPException::forInvalidStatusCode($code);
        }

        
        if (! array_key_exists($code, static::$statusCodes) && ($reason === '')) {
            throw HTTPException::forUnkownStatusCode($code);
        }

        $this->statusCode = $code;

        $this->reason = ($reason !== '') ? $reason : static::$statusCodes[$code];

        return $this;
    }

    
    
    

    
    public function setDate(DateTime $date)
    {
        $date->setTimezone(new DateTimeZone('UTC'));

        $this->setHeader('Date', $date->format('D, d M Y H:i:s') . ' GMT');

        return $this;
    }

    
    public function setLink(PagerInterface $pager)
    {
        $links    = '';
        $previous = $pager->getPreviousPageURI();

        if (is_string($previous) && $previous !== '') {
            $links .= '<' . $pager->getPageURI($pager->getFirstPage()) . '>; rel="first",';
            $links .= '<' . $previous . '>; rel="prev"';
        }

        $next = $pager->getNextPageURI();

        if (is_string($next) && $next !== '' && is_string($previous) && $previous !== '') {
            $links .= ',';
        }

        if (is_string($next) && $next !== '') {
            $links .= '<' . $next . '>; rel="next",';
            $links .= '<' . $pager->getPageURI($pager->getLastPage()) . '>; rel="last"';
        }

        $this->setHeader('Link', $links);

        return $this;
    }

    
    public function setContentType(string $mime, string $charset = 'UTF-8')
    {
        
        if ((strpos($mime, 'charset=') < 1) && ($charset !== '')) {
            $mime .= '; charset=' . $charset;
        }

        $this->removeHeader('Content-Type'); 
        $this->setHeader('Content-Type', $mime);

        return $this;
    }

    
    public function setJSON($body, bool $unencoded = false)
    {
        $this->body = $this->formatBody($body, 'json' . ($unencoded ? '-unencoded' : ''));

        return $this;
    }

    
    public function getJSON()
    {
        $body = $this->body;

        if ($this->bodyFormat !== 'json') {
            $body = service('format')->getFormatter('application/json')->format($body);
        }

        return $body ?: null;
    }

    
    public function setXML($body)
    {
        $this->body = $this->formatBody($body, 'xml');

        return $this;
    }

    
    public function getXML()
    {
        $body = $this->body;

        if ($this->bodyFormat !== 'xml') {
            $body = service('format')->getFormatter('application/xml')->format($body);
        }

        return $body;
    }

    
    protected function formatBody($body, string $format)
    {
        $this->bodyFormat = ($format === 'json-unencoded' ? 'json' : $format);
        $mime             = "application/{$this->bodyFormat}";
        $this->setContentType($mime);

        
        if (! is_string($body) || $format === 'json-unencoded') {
            $body = service('format')->getFormatter($mime)->format($body);
        }

        return $body;
    }

    
    
    
    
    

    
    public function noCache()
    {
        $this->removeHeader('Cache-Control');
        $this->setHeader('Cache-Control', ['no-store', 'max-age=0', 'no-cache']);

        return $this;
    }

    
    public function setCache(array $options = [])
    {
        if ($options === []) {
            return $this;
        }

        $this->removeHeader('Cache-Control');
        $this->removeHeader('ETag');

        
        if (isset($options['etag'])) {
            $this->setHeader('ETag', $options['etag']);
            unset($options['etag']);
        }

        
        if (isset($options['last-modified'])) {
            $this->setLastModified($options['last-modified']);

            unset($options['last-modified']);
        }

        $this->setHeader('Cache-Control', $options);

        return $this;
    }

    
    public function setLastModified($date)
    {
        if ($date instanceof DateTime) {
            $date->setTimezone(new DateTimeZone('UTC'));
            $this->setHeader('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT');
        } elseif (is_string($date)) {
            $this->setHeader('Last-Modified', $date);
        }

        return $this;
    }

    
    
    

    
    public function send()
    {
        
        
        $this->CSP->finalize($this);

        $this->sendHeaders();
        $this->sendCookies();
        $this->sendBody();

        return $this;
    }

    
    public function sendHeaders()
    {
        
        if ($this->pretend || headers_sent()) {
            return $this;
        }

        
        
        if (! isset($this->headers['Date']) && PHP_SAPI !== 'cli-server') {
            $this->setDate(DateTime::createFromFormat('U', (string) Time::now()->getTimestamp()));
        }

        
        header(sprintf('HTTP/%s %s %s', $this->getProtocolVersion(), $this->getStatusCode(), $this->getReasonPhrase()), true, $this->getStatusCode());

        
        foreach ($this->headers() as $name => $value) {
            if ($value instanceof Header) {
                header(
                    $name . ': ' . $value->getValueLine(),
                    true,
                    $this->getStatusCode(),
                );
            } else {
                $replace = true;

                foreach ($value as $header) {
                    header(
                        $name . ': ' . $header->getValueLine(),
                        $replace,
                        $this->getStatusCode(),
                    );
                    $replace = false;
                }
            }
        }

        return $this;
    }

    
    public function sendBody()
    {
        echo $this->body;

        return $this;
    }

    
    public function redirect(string $uri, string $method = 'auto', ?int $code = null)
    {
        
        $superglobals   = service('superglobals');
        $serverSoftware = $superglobals->server('SERVER_SOFTWARE');
        if (
            $method === 'auto'
            && $serverSoftware !== null
            && str_contains($serverSoftware, 'Microsoft-IIS')
        ) {
            $method = 'refresh';
        } elseif ($method !== 'refresh' && $code === null) {
            
            $serverProtocol = $superglobals->server('SERVER_PROTOCOL');
            $requestMethod  = $superglobals->server('REQUEST_METHOD');
            if (
                $serverProtocol !== null
                && $requestMethod !== null
                && $this->getProtocolVersion() >= 1.1
            ) {
                if ($requestMethod === Method::GET) {
                    $code = 302;
                } elseif (in_array($requestMethod, [Method::POST, Method::PUT, Method::DELETE], true)) {
                    
                    $code = 303;
                } else {
                    $code = 307;
                }
            }
        }

        if ($code === null) {
            $code = 302;
        }

        match ($method) {
            'refresh' => $this->setHeader('Refresh', '0;url=' . $uri),
            default   => $this->setHeader('Location', $uri),
        };

        $this->setStatusCode($code);

        return $this;
    }

    
    public function setCookie(
        $name,
        $value = '',
        $expire = 0,
        $domain = '',
        $path = '/',
        $prefix = '',
        $secure = null,
        $httponly = null,
        $samesite = null,
    ) {
        if ($name instanceof Cookie) {
            $this->cookieStore = $this->cookieStore->put($name);

            return $this;
        }

        $cookieConfig = config(CookieConfig::class);

        $secure ??= $cookieConfig->secure;
        $httponly ??= $cookieConfig->httponly;
        $samesite ??= $cookieConfig->samesite;

        if (is_array($name)) {
            
            foreach (['samesite', 'value', 'expire', 'domain', 'path', 'prefix', 'secure', 'httponly', 'name'] as $item) {
                if (isset($name[$item])) {
                    ${$item} = $name[$item];
                }
            }
        }

        if (is_numeric($expire)) {
            $expire = $expire > 0 ? Time::now()->getTimestamp() + $expire : 0;
        }

        $cookie = new Cookie($name, $value, [
            'expires'  => $expire ?: 0,
            'domain'   => $domain,
            'path'     => $path,
            'prefix'   => $prefix,
            'secure'   => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite ?? '',
        ]);

        $this->cookieStore = $this->cookieStore->put($cookie);

        return $this;
    }

    
    public function getCookieStore()
    {
        return $this->cookieStore;
    }

    
    public function hasCookie(string $name, ?string $value = null, string $prefix = ''): bool
    {
        $prefix = $prefix !== '' ? $prefix : Cookie::setDefaults()['prefix']; 

        return $this->cookieStore->has($name, $prefix, $value);
    }

    
    public function getCookie(?string $name = null, string $prefix = '')
    {
        if ((string) $name === '') {
            return $this->cookieStore->display();
        }

        try {
            $prefix = $prefix !== '' ? $prefix : Cookie::setDefaults()['prefix']; 

            return $this->cookieStore->get($name, $prefix);
        } catch (CookieException $e) {
            log_message('error', (string) $e);

            return null;
        }
    }

    
    public function deleteCookie(string $name = '', string $domain = '', string $path = '/', string $prefix = '')
    {
        if ($name === '') {
            return $this;
        }

        $prefix = $prefix !== '' ? $prefix : Cookie::setDefaults()['prefix']; 

        $prefixed = $prefix . $name;
        $store    = $this->cookieStore;
        $found    = false;

        
        foreach ($store as $cookie) {
            if ($cookie->getPrefixedName() === $prefixed) {
                if ($domain !== $cookie->getDomain()) {
                    continue;
                }

                if ($path !== $cookie->getPath()) {
                    continue;
                }

                $cookie = $cookie->withValue('')->withExpired();
                $found  = true;

                $this->cookieStore = $store->put($cookie);
                break;
            }
        }

        if (! $found) {
            $this->setCookie($name, '', 0, $domain, $path, $prefix);
        }

        return $this;
    }

    
    public function getCookies()
    {
        return $this->cookieStore->display();
    }

    
    protected function sendCookies()
    {
        if ($this->pretend) {
            return;
        }

        $this->dispatchCookies();
    }

    private function dispatchCookies(): void
    {
        
        $request = service('request');

        foreach ($this->cookieStore->display() as $cookie) {
            if ($cookie->isSecure() && ! $request->isSecure()) {
                throw SecurityException::forInsecureCookie();
            }

            $name    = $cookie->getPrefixedName();
            $value   = $cookie->getValue();
            $options = $cookie->getOptions();

            if ($cookie->isRaw()) {
                $this->doSetRawCookie($name, $value, $options);
            } else {
                $this->doSetCookie($name, $value, $options);
            }
        }

        $this->cookieStore->clear();
    }

    
    private function doSetRawCookie(string $name, string $value, array $options): void
    {
        setrawcookie($name, $value, $options);
    }

    
    private function doSetCookie(string $name, string $value, array $options): void
    {
        setcookie($name, $value, $options);
    }

    
    public function download(string $filename = '', $data = '', bool $setMime = false)
    {
        if ($filename === '' || $data === '') {
            return null;
        }

        $filepath = '';
        if ($data === null) {
            $filepath = $filename;
            $filename = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $filename));
            $filename = end($filename);
        }

        $response = new DownloadResponse($filename, $setMime);

        if ($filepath !== '') {
            $response->setFilePath($filepath);
        } elseif ($data !== null) {
            $response->setBinary($data);
        }

        return $response;
    }

    public function getCSP(): ContentSecurityPolicy
    {
        return $this->CSP;
    }
}
