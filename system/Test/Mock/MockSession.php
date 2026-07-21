<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\Cookie\Cookie;
use CodeIgniter\I18n\Time;
use CodeIgniter\Session\Session;


class MockSession extends Session
{
    
    public array $cookies = [];

    public bool $didRegenerate = false;

    
    protected function setSaveHandler()
    {
        
    }

    
    protected function startSession()
    {
        
        $this->setCookie();
    }

    
    protected function setCookie()
    {
        $expiration   = $this->config->expiration === 0 ? 0 : Time::now()->getTimestamp() + $this->config->expiration;
        $this->cookie = $this->cookie->withValue(session_id())->withExpires($expiration);

        $this->cookies[] = $this->cookie;
    }

    
    public function regenerate(bool $destroy = false)
    {
        $this->didRegenerate              = true;
        $_SESSION['__ci_last_regenerate'] = Time::now()->getTimestamp();
    }
}
