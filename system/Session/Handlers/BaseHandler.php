<?php

declare(strict_types=1);



namespace CodeIgniter\Session\Handlers;

use Config\Cookie as CookieConfig;
use Config\Session as SessionConfig;
use Psr\Log\LoggerAwareTrait;
use SessionHandlerInterface;


abstract class BaseHandler implements SessionHandlerInterface
{
    use LoggerAwareTrait;

    
    protected $fingerprint;

    
    protected $lock = false;

    
    protected $cookiePrefix = '';

    
    protected $cookieDomain = '';

    
    protected $cookiePath = '/';

    
    protected $cookieSecure = false;

    
    protected $cookieName;

    
    protected $matchIP = false;

    
    protected $sessionID;

    
    protected $savePath;

    
    protected $ipAddress;

    public function __construct(SessionConfig $config, string $ipAddress)
    {
        
        $this->cookieName = $config->cookieName;
        $this->matchIP    = $config->matchIP;
        $this->savePath   = $config->savePath;

        $cookie = config(CookieConfig::class);

        
        $this->cookieDomain = $cookie->domain;
        $this->cookiePath   = $cookie->path;
        $this->cookieSecure = $cookie->secure;

        $this->ipAddress = $ipAddress;
    }

    
    protected function destroyCookie(): bool
    {
        return setcookie(
            $this->cookieName,
            '',
            ['expires' => 1, 'path' => $this->cookiePath, 'domain' => $this->cookieDomain, 'secure' => $this->cookieSecure, 'httponly' => true],
        );
    }

    
    protected function lockSession(string $sessionID): bool
    {
        $this->lock = true;

        return true;
    }

    
    protected function releaseLock(): bool
    {
        $this->lock = false;

        return true;
    }

    
    protected function fail(): bool
    {
        ini_set('session.save_path', $this->savePath);

        return false;
    }
}
