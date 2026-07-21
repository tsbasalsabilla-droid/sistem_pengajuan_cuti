<?php

declare(strict_types=1);



namespace CodeIgniter\Security;

use CodeIgniter\Cookie\Cookie;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Exceptions\LogicException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Method;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\I18n\Time;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Session\Session;
use Config\Cookie as CookieConfig;
use Config\Security as SecurityConfig;
use ErrorException;
use JsonException;
use SensitiveParameter;


class Security implements SecurityInterface
{
    public const CSRF_PROTECTION_COOKIE  = 'cookie';
    public const CSRF_PROTECTION_SESSION = 'session';
    protected const CSRF_HASH_BYTES      = 16;

    
    protected $csrfProtection = self::CSRF_PROTECTION_COOKIE;

    
    protected $tokenRandomize = false;

    
    protected $hash;

    
    protected $tokenName = 'csrf_token_name';

    
    protected $headerName = 'X-CSRF-TOKEN';

    
    protected $cookie;

    
    protected $cookieName = 'csrf_cookie_name';

    
    protected $expires = 7200;

    
    protected $regenerate = true;

    
    protected $redirect = false;

    
    protected $samesite = Cookie::SAMESITE_LAX;

    private  IncomingRequest $request;

    
    private ?string $rawCookieName = null;

    
    private ?Session $session = null;

    
    private ?string $hashInCookie = null;

    
    protected SecurityConfig $config;

    
    public function __construct(SecurityConfig $config)
    {
        $this->config = $config;

        $this->rawCookieName = $config->cookieName;

        if ($this->isCSRFCookie()) {
            $cookie = config(CookieConfig::class);

            $this->configureCookie($cookie);
        } else {
            
            $this->configureSession();
        }

        $this->request      = service('request');
        $this->hashInCookie = $this->request->getCookie($this->cookieName);

        $this->restoreHash();
        if ($this->hash === null) {
            $this->generateHash();
        }
    }

    private function isCSRFCookie(): bool
    {
        return $this->config->csrfProtection === self::CSRF_PROTECTION_COOKIE;
    }

    private function configureSession(): void
    {
        $this->session = service('session');
    }

    private function configureCookie(CookieConfig $cookie): void
    {
        $cookiePrefix     = $cookie->prefix;
        $this->cookieName = $cookiePrefix . $this->rawCookieName;
        Cookie::setDefaults($cookie);
    }

    public function verify(RequestInterface $request)
    {
        $method = $request->getMethod();

        
        if (! in_array($method, [Method::POST, Method::PUT, Method::DELETE, Method::PATCH], true)) {
            return $this;
        }

        assert($request instanceof IncomingRequest);

        $postedToken = $this->getPostedToken($request);

        try {
            $token = $postedToken !== null && $this->config->tokenRandomize
                ? $this->derandomize($postedToken)
                : $postedToken;
        } catch (InvalidArgumentException) {
            $token = null;
        }

        if (! isset($token, $this->hash) || ! hash_equals($this->hash, $token)) {
            throw SecurityException::forDisallowedAction();
        }

        $this->removeTokenInRequest($request);

        if ($this->config->regenerate) {
            $this->generateHash();
        }

        log_message('info', 'CSRF token verified.');

        return $this;
    }

    
    private function removeTokenInRequest(IncomingRequest $request): void
    {
        $superglobals = service('superglobals');
        $tokenName    = $this->config->tokenName;

        
        if (is_string($superglobals->post($tokenName))) {
            $superglobals->unsetPost($tokenName);
            $request->setGlobal('post', $superglobals->getPostArray());

            return;
        }

        $body = $request->getBody() ?? '';

        if ($body === '') {
            return;
        }

        
        try {
            $json = json_decode($body, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $json = null;
        }

        if (is_object($json)) {
            if (property_exists($json, $tokenName)) {
                unset($json->{$tokenName});
                $request->setBody(json_encode($json));
            }

            return;
        }

        
        parse_str($body, $result);

        unset($result[$tokenName]);
        $request->setBody(http_build_query($result));
    }

    private function getPostedToken(IncomingRequest $request): ?string
    {
        $tokenName  = $this->config->tokenName;
        $headerName = $this->config->headerName;

        
        $token = $request->getPost($tokenName);

        if ($this->isNonEmptyTokenString($token)) {
            return $token;
        }

        
        if ($request->hasHeader($headerName)) {
            $token = $request->header($headerName)->getValue();

            if ($this->isNonEmptyTokenString($token)) {
                return $token;
            }
        }

        
        $body = $request->getBody() ?? '';

        if ($body === '') {
            return null;
        }

        
        try {
            $json = json_decode($body, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $json = null;
        }

        if (is_object($json) && property_exists($json, $tokenName)) {
            $token = $json->{$tokenName};

            if ($this->isNonEmptyTokenString($token)) {
                return $token;
            }
        }

        
        parse_str($body, $result);
        $token = $result[$tokenName] ?? null;

        if ($this->isNonEmptyTokenString($token)) {
            return $token;
        }

        return null;
    }

    
    private function isNonEmptyTokenString(mixed $token): bool
    {
        return is_string($token) && $token !== '';
    }

    
    public function getHash(): ?string
    {
        return $this->config->tokenRandomize ? $this->randomize($this->hash) : $this->hash;
    }

    
    protected function randomize(string $hash): string
    {
        $keyBinary  = random_bytes(static::CSRF_HASH_BYTES);
        $hashBinary = hex2bin($hash);

        if ($hashBinary === false) {
            throw new LogicException('$hash is invalid: ' . $hash);
        }

        return bin2hex(($hashBinary ^ $keyBinary) . $keyBinary);
    }

    
    protected function derandomize(#[SensitiveParameter] string $token): string
    {
        $key   = substr($token, -static::CSRF_HASH_BYTES * 2);
        $value = substr($token, 0, static::CSRF_HASH_BYTES * 2);

        try {
            return bin2hex((string) hex2bin($value) ^ (string) hex2bin($key));
        } catch (ErrorException $e) {
            
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    
    public function getTokenName(): string
    {
        return $this->config->tokenName;
    }

    
    public function getHeaderName(): string
    {
        return $this->config->headerName;
    }

    
    public function getCookieName(): string
    {
        return $this->config->cookieName;
    }

    
    public function shouldRedirect(): bool
    {
        return $this->config->redirect;
    }

    
    public function sanitizeFilename(string $str, bool $relativePath = false): string
    {
        helper('security');

        return sanitize_filename($str, $relativePath);
    }

    
    private function restoreHash(): void
    {
        if ($this->isCSRFCookie()) {
            if ($this->isHashInCookie()) {
                $this->hash = $this->hashInCookie;
            }
        } elseif ($this->session->has($this->config->tokenName)) {
            
            $this->hash = $this->session->get($this->config->tokenName);
        }
    }

    
    public function generateHash(): string
    {
        $this->hash = bin2hex(random_bytes(static::CSRF_HASH_BYTES));

        if ($this->isCSRFCookie()) {
            $this->saveHashInCookie();
        } else {
            
            $this->saveHashInSession();
        }

        return $this->hash;
    }

    private function isHashInCookie(): bool
    {
        if ($this->hashInCookie === null) {
            return false;
        }

        $length  = static::CSRF_HASH_BYTES * 2;
        $pattern = '#^[0-9a-f]{' . $length . '}$#iS';

        return preg_match($pattern, $this->hashInCookie) === 1;
    }

    private function saveHashInCookie(): void
    {
        $this->cookie = new Cookie(
            $this->rawCookieName,
            $this->hash,
            [
                'expires' => $this->config->expires === 0 ? 0 : Time::now()->getTimestamp() + $this->config->expires,
            ],
        );

        $response = service('response');
        $response->setCookie($this->cookie);
    }

    private function saveHashInSession(): void
    {
        $this->session->set($this->config->tokenName, $this->hash);
    }
}
