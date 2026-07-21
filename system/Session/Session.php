<?php

declare(strict_types=1);



namespace CodeIgniter\Session;

use CodeIgniter\Cookie\Cookie;
use CodeIgniter\I18n\Time;
use Config\Cookie as CookieConfig;
use Config\Session as SessionConfig;
use Psr\Log\LoggerAwareTrait;
use SessionHandlerInterface;


class Session implements SessionInterface
{
    use LoggerAwareTrait;

    
    protected $driver;

    
    protected $cookie;

    
    protected $sidRegexp;

    protected SessionConfig $config;

    
    public function __construct(SessionHandlerInterface $driver, SessionConfig $config)
    {
        $this->driver = $driver;
        $this->config = $config;
        $cookie       = config(CookieConfig::class);

        $this->cookie = (new Cookie($this->config->cookieName, '', [
            'expires'  => $this->config->expiration === 0 ? 0 : Time::now()->getTimestamp() + $this->config->expiration,
            'path'     => $cookie->path,
            'domain'   => $cookie->domain,
            'secure'   => $cookie->secure,
            'httponly' => true, 
            'samesite' => $cookie->samesite ?? Cookie::SAMESITE_LAX,
            'raw'      => $cookie->raw ?? false,
        ]))->withPrefix(''); 

        helper('array');
    }

    
    public function start()
    {
        if (is_cli() && ENVIRONMENT !== 'testing') {
            
            $this->logger->debug('Session: Initialization under CLI aborted.');

            return null;
            
        }

        if ((bool) ini_get('session.auto_start')) {
            $this->logger->error('Session: session.auto_start is enabled in php.ini. Aborting.');

            return null;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->logger->warning('Session: Sessions is enabled, and one exists. Please don\'t $session->start();');

            return null;
        }

        $this->configure();
        $this->setSaveHandler();

        
        if (
            isset($_COOKIE[$this->config->cookieName])
            && (! is_string($_COOKIE[$this->config->cookieName]) || preg_match('#\A' . $this->sidRegexp . '\z#', $_COOKIE[$this->config->cookieName]) !== 1)
        ) {
            unset($_COOKIE[$this->config->cookieName]);
        }

        $this->startSession();

        
        $requestedWith = service('superglobals')->server('HTTP_X_REQUESTED_WITH');
        if (($requestedWith === null || strtolower($requestedWith) !== 'xmlhttprequest')
            && ($regenerateTime = $this->config->timeToUpdate) > 0
        ) {
            if (! isset($_SESSION['__ci_last_regenerate'])) {
                $_SESSION['__ci_last_regenerate'] = Time::now()->getTimestamp();
            } elseif ($_SESSION['__ci_last_regenerate'] < (Time::now()->getTimestamp() - $regenerateTime)) {
                $this->regenerate($this->config->regenerateDestroy);
            }
        }
        
        
        elseif (isset($_COOKIE[$this->config->cookieName]) && $_COOKIE[$this->config->cookieName] === session_id()) {
            $this->setCookie();
        }

        $this->initVars();
        $this->logger->debug("Session: Class initialized using '" . $this->config->driver . "' driver.");

        return $this;
    }

    
    protected function configure()
    {
        ini_set('session.name', $this->config->cookieName);

        $sameSite = $this->cookie->getSameSite() === ''
            ? ucfirst(Cookie::SAMESITE_LAX)
            : $this->cookie->getSameSite();

        $params = [
            'lifetime' => $this->config->expiration,
            'path'     => $this->cookie->getPath(),
            'domain'   => $this->cookie->getDomain(),
            'secure'   => $this->cookie->isSecure(),
            'httponly' => true, 
            'samesite' => $sameSite,
        ];

        ini_set('session.cookie_samesite', $sameSite);
        session_set_cookie_params($params);

        if ($this->config->expiration > 0) {
            ini_set('session.gc_maxlifetime', (string) $this->config->expiration);
        }

        if ($this->config->savePath !== '') {
            ini_set('session.save_path', $this->config->savePath);
        }

        
        ini_set('session.use_trans_sid', '0');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');

        $this->configureSidLength();
    }

    
    protected function configureSidLength()
    {
        $bitsPerCharacter = (int) ini_get('session.sid_bits_per_character');
        $sidLength        = (int) ini_get('session.sid_length');

        
        if (PHP_VERSION_ID < 90000) {
            if ($bitsPerCharacter !== 4) {
                ini_set('session.sid_bits_per_character', '4');
            }
            if ($sidLength !== 32) {
                ini_set('session.sid_length', '32');
            }
        }

        $this->sidRegexp = '[0-9a-f]{32}';
    }

    
    protected function initVars()
    {
        if (! isset($_SESSION['__ci_vars'])) {
            return;
        }

        $currentTime = Time::now()->getTimestamp();

        foreach ($_SESSION['__ci_vars'] as $key => &$value) {
            if ($value === 'new') {
                $_SESSION['__ci_vars'][$key] = 'old';
            }
            
            elseif ($value === 'old' || $value < $currentTime) {
                unset($_SESSION[$key], $_SESSION['__ci_vars'][$key]);
            }
        }

        if ($_SESSION['__ci_vars'] === []) {
            unset($_SESSION['__ci_vars']);
        }
    }

    public function regenerate(bool $destroy = false)
    {
        $_SESSION['__ci_last_regenerate'] = Time::now()->getTimestamp();
        session_regenerate_id($destroy);

        $this->removeOldSessionCookie();
    }

    private function removeOldSessionCookie(): void
    {
        $response              = service('response');
        $cookieStoreInResponse = $response->getCookieStore();

        if (! $cookieStoreInResponse->has($this->config->cookieName)) {
            return;
        }

        
        $newCookieStore = $cookieStoreInResponse->remove($this->config->cookieName);

        
        $cookieStoreInResponse->clear();

        foreach ($newCookieStore as $cookie) {
            $response->setCookie($cookie);
        }
    }

    public function destroy()
    {
        if (ENVIRONMENT === 'testing') {
            return;
        }

        session_destroy();
    }

    
    public function close()
    {
        if (ENVIRONMENT === 'testing') {
            return;
        }

        session_write_close();
    }

    public function set($data, $value = null)
    {
        $data = is_array($data) ? $data : [$data => $value];

        if (array_is_list($data)) {
            $data = array_fill_keys($data, null);
        }

        foreach ($data as $sessionKey => $sessionValue) {
            $_SESSION[$sessionKey] = $sessionValue;
        }
    }

    public function get(?string $key = null)
    {
        if (! isset($_SESSION) || $_SESSION === []) {
            return $key === null ? [] : null;
        }

        $key ??= '';

        if ($key !== '') {
            return $_SESSION[$key] ?? dot_array_search($key, $_SESSION);
        }

        $userdata = [];
        $exclude  = array_merge(['__ci_vars'], $this->getFlashKeys(), $this->getTempKeys());

        foreach (array_keys($_SESSION) as $key) {
            if (! in_array($key, $exclude, true)) {
                $userdata[$key] = $_SESSION[$key];
            }
        }

        return $userdata;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    
    public function push(string $key, array $data)
    {
        if ($this->has($key) && is_array($value = $this->get($key))) {
            $this->set($key, array_merge($value, $data));
        }
    }

    public function remove($key)
    {
        $key = is_array($key) ? $key : [$key];

        foreach ($key as $k) {
            unset($_SESSION[$k]);
        }
    }

    
    public function __set(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    
    public function __get(string $key)
    {
        
        
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        if ($key === 'session_id') {
            return session_id();
        }

        return null;
    }

    
    public function __isset(string $key): bool
    {
        return isset($_SESSION[$key]) || $key === 'session_id';
    }

    public function setFlashdata($data, $value = null)
    {
        $this->set($data, $value);
        $this->markAsFlashdata(is_array($data) ? array_keys($data) : $data);
    }

    public function getFlashdata(?string $key = null)
    {
        $_SESSION['__ci_vars'] ??= [];

        if (isset($key)) {
            if (! isset($_SESSION['__ci_vars'][$key]) || is_int($_SESSION['__ci_vars'][$key])) {
                return null;
            }

            return $_SESSION[$key] ?? null;
        }

        $flashdata = [];

        foreach ($_SESSION['__ci_vars'] as $key => $value) {
            if (! is_int($value)) {
                $flashdata[$key] = $_SESSION[$key];
            }
        }

        return $flashdata;
    }

    public function keepFlashdata($key)
    {
        $this->markAsFlashdata($key);
    }

    public function markAsFlashdata($key): bool
    {
        $keys = is_array($key) ? $key : [$key];

        foreach ($keys as $sessionKey) {
            if (! isset($_SESSION[$sessionKey])) {
                return false;
            }
        }

        $_SESSION['__ci_vars'] ??= [];
        $_SESSION['__ci_vars'] = [...$_SESSION['__ci_vars'], ...array_fill_keys($keys, 'new')];

        return true;
    }

    public function unmarkFlashdata($key)
    {
        if (! isset($_SESSION['__ci_vars'])) {
            return;
        }

        if (! is_array($key)) {
            $key = [$key];
        }

        foreach ($key as $k) {
            if (isset($_SESSION['__ci_vars'][$k]) && ! is_int($_SESSION['__ci_vars'][$k])) {
                unset($_SESSION['__ci_vars'][$k]);
            }
        }

        if ($_SESSION['__ci_vars'] === []) {
            unset($_SESSION['__ci_vars']);
        }
    }

    public function getFlashKeys(): array
    {
        if (! isset($_SESSION['__ci_vars'])) {
            return [];
        }

        $keys = [];

        foreach (array_keys($_SESSION['__ci_vars']) as $key) {
            if (! is_int($_SESSION['__ci_vars'][$key])) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public function setTempdata($data, $value = null, int $ttl = 300)
    {
        $this->set($data, $value);
        $this->markAsTempdata($data, $ttl);
    }

    public function getTempdata(?string $key = null)
    {
        $_SESSION['__ci_vars'] ??= [];

        if (isset($key)) {
            if (! isset($_SESSION['__ci_vars'][$key]) || ! is_int($_SESSION['__ci_vars'][$key])) {
                return null;
            }

            return $_SESSION[$key] ?? null;
        }

        $tempdata = [];

        foreach ($_SESSION['__ci_vars'] as $key => $value) {
            if (is_int($value)) {
                $tempdata[$key] = $_SESSION[$key];
            }
        }

        return $tempdata;
    }

    public function removeTempdata(string $key)
    {
        $this->unmarkTempdata($key);
        unset($_SESSION[$key]);
    }

    public function markAsTempdata($key, int $ttl = 300): bool
    {
        $time = Time::now()->getTimestamp();
        $keys = is_array($key) ? $key : [$key];

        if (array_is_list($keys)) {
            $keys = array_fill_keys($keys, $ttl);
        }

        $tempdata = [];

        foreach ($keys as $sessionKey => $timeToLive) {
            if (! array_key_exists($sessionKey, $_SESSION)) {
                return false;
            }

            if (is_int($timeToLive)) {
                $timeToLive += $time;
            } else {
                $timeToLive = $time + $ttl;
            }

            $tempdata[$sessionKey] = $timeToLive;
        }

        $_SESSION['__ci_vars'] ??= [];
        $_SESSION['__ci_vars'] = [...$_SESSION['__ci_vars'], ...$tempdata];

        return true;
    }

    public function unmarkTempdata($key)
    {
        if (! isset($_SESSION['__ci_vars'])) {
            return;
        }

        if (! is_array($key)) {
            $key = [$key];
        }

        foreach ($key as $k) {
            if (isset($_SESSION['__ci_vars'][$k]) && is_int($_SESSION['__ci_vars'][$k])) {
                unset($_SESSION['__ci_vars'][$k]);
            }
        }

        if ($_SESSION['__ci_vars'] === []) {
            unset($_SESSION['__ci_vars']);
        }
    }

    public function getTempKeys(): array
    {
        if (! isset($_SESSION['__ci_vars'])) {
            return [];
        }

        $keys = [];

        foreach (array_keys($_SESSION['__ci_vars']) as $key) {
            if (is_int($_SESSION['__ci_vars'][$key])) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    
    protected function setSaveHandler()
    {
        session_set_save_handler($this->driver, true);
    }

    
    protected function startSession()
    {
        if (ENVIRONMENT === 'testing') {
            $_SESSION = [];

            return;
        }

        session_start(); 
    }

    
    protected function setCookie()
    {
        $expiration   = $this->config->expiration === 0 ? 0 : Time::now()->getTimestamp() + $this->config->expiration;
        $this->cookie = $this->cookie->withValue(session_id())->withExpires($expiration);

        $response = service('response');
        $response->setCookie($this->cookie);
    }
}
