<?php

declare(strict_types=1);



namespace CodeIgniter\Config;

use CodeIgniter\Autoloader\Autoloader;
use CodeIgniter\Autoloader\FileLocator;
use CodeIgniter\Autoloader\FileLocatorCached;
use CodeIgniter\Autoloader\FileLocatorInterface;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Cache\ResponseCache;
use CodeIgniter\CLI\Commands;
use CodeIgniter\CodeIgniter;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\MigrationRunner;
use CodeIgniter\Debug\Exceptions;
use CodeIgniter\Debug\Iterator;
use CodeIgniter\Debug\Timer;
use CodeIgniter\Debug\Toolbar;
use CodeIgniter\Email\Email;
use CodeIgniter\Encryption\EncrypterInterface;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Filters\Filters;
use CodeIgniter\Format\Format;
use CodeIgniter\Honeypot\Honeypot;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\ContentSecurityPolicy;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Negotiate;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\SiteURIFactory;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Images\Handlers\BaseHandler;
use CodeIgniter\Language\Language;
use CodeIgniter\Log\Logger;
use CodeIgniter\Pager\Pager;
use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Router\RouteCollectionInterface;
use CodeIgniter\Router\Router;
use CodeIgniter\Security\Security;
use CodeIgniter\Session\Session;
use CodeIgniter\Superglobals;
use CodeIgniter\Throttle\Throttler;
use CodeIgniter\Typography\Typography;
use CodeIgniter\Validation\ValidationInterface;
use CodeIgniter\View\Cell;
use CodeIgniter\View\Parser;
use CodeIgniter\View\RendererInterface;
use CodeIgniter\View\View;
use Config\App;
use Config\Autoload;
use Config\Cache;
use Config\ContentSecurityPolicy as CSPConfig;
use Config\Encryption;
use Config\Exceptions as ConfigExceptions;
use Config\Filters as ConfigFilters;
use Config\Format as ConfigFormat;
use Config\Honeypot as ConfigHoneyPot;
use Config\Images;
use Config\Migrations;
use Config\Modules;
use Config\Optimize;
use Config\Pager as ConfigPager;
use Config\Services as AppServices;
use Config\Session as ConfigSession;
use Config\Toolbar as ConfigToolbar;
use Config\Validation as ConfigValidation;
use Config\View as ConfigView;
use Config\WorkerMode;


class BaseService
{
    
    protected static $instances = [];

    
    protected static array $factories = [];

    
    protected static $mocks = [];

    
    protected static $discovered = false;

    
    protected static $services = [];

    
    private static array $serviceNames = [];

    
    public static function get(string $key): ?object
    {
        return static::$instances[$key] ?? static::__callStatic($key, []);
    }

    
    public static function has(string $key): bool
    {
        return isset(static::$instances[$key]);
    }

    
    public static function set(string $key, object $value): void
    {
        if (isset(static::$instances[$key])) {
            throw new InvalidArgumentException('The entry for "' . $key . '" is already set.');
        }

        static::$instances[$key] = $value;
    }

    
    public static function override(string $key, object $value): void
    {
        static::$instances[$key] = $value;
    }

    
    protected static function getSharedInstance(string $key, ...$params)
    {
        $key = strtolower($key);

        
        if (isset(static::$mocks[$key])) {
            return static::$mocks[$key];
        }

        if (! isset(static::$instances[$key])) {
            
            $params[] = false;

            static::$instances[$key] = AppServices::$key(...$params);
        }

        return static::$instances[$key];
    }

    
    public static function autoloader(bool $getShared = true)
    {
        if ($getShared) {
            if (empty(static::$instances['autoloader'])) {
                static::$instances['autoloader'] = new Autoloader();
            }

            return static::$instances['autoloader'];
        }

        return new Autoloader();
    }

    
    public static function locator(bool $getShared = true)
    {
        if ($getShared) {
            if (empty(static::$instances['locator'])) {
                $cacheEnabled = class_exists(Optimize::class)
                    && (new Optimize())->locatorCacheEnabled;

                if ($cacheEnabled) {
                    static::$instances['locator'] = new FileLocatorCached(new FileLocator(static::autoloader()));
                } else {
                    static::$instances['locator'] = new FileLocator(static::autoloader());
                }
            }

            return static::$mocks['locator'] ?? static::$instances['locator'];
        }

        return new FileLocator(static::autoloader());
    }

    
    public static function __callStatic(string $name, array $arguments)
    {
        if (isset(static::$factories[$name])) {
            return static::$factories[$name](...$arguments);
        }

        $service = static::serviceExists($name);

        if ($service === null) {
            return null;
        }

        return $service::$name(...$arguments);
    }

    
    public static function serviceExists(string $name): ?string
    {
        static::buildServicesCache();

        $services = array_merge(self::$serviceNames, [Services::class]);
        $name     = strtolower($name);

        foreach ($services as $service) {
            if (method_exists($service, $name)) {
                static::$factories[$name] = [$service, $name];

                return $service;
            }
        }

        return null;
    }

    
    public static function reset(bool $initAutoloader = true)
    {
        static::$mocks     = [];
        static::$instances = [];
        static::$factories = [];

        if ($initAutoloader) {
            static::autoloader()->initialize(new Autoload(), new Modules());
        }
    }

    
    public static function reconnectCacheForWorkerMode(): void
    {
        if (! isset(static::$instances['cache'])) {
            return;
        }

        $cache = static::$instances['cache'];

        if (! $cache->ping()) {
            $cache->reconnect();
        }
    }

    
    public static function resetForWorkerMode(WorkerMode $config): void
    {
        
        static::$mocks = [];

        
        static::$factories = [];

        
        $persistentInstances = [];

        foreach (static::$instances as $serviceName => $service) {
            
            if (in_array($serviceName, $config->persistentServices, true)) {
                $persistentInstances[$serviceName] = $service;
            }
        }

        static::$instances = $persistentInstances;
    }

    
    public static function resetSingle(string $name)
    {
        $name = strtolower($name);
        unset(static::$mocks[$name], static::$instances[$name]);
    }

    
    public static function injectMock(string $name, $mock)
    {
        static::$instances[$name]         = $mock;
        static::$mocks[strtolower($name)] = $mock;
    }

    
    public static function resetServicesCache(): void
    {
        self::$serviceNames = [];
        static::$discovered = false;
    }

    protected static function buildServicesCache(): void
    {
        if (! static::$discovered) {
            if ((new Modules())->shouldDiscover('services')) {
                $locator = static::locator();
                $files   = $locator->search('Config/Services');

                $systemPath = static::autoloader()->getNamespace('CodeIgniter')[0];

                
                foreach ($files as $file) {
                    
                    if (str_starts_with($file, $systemPath)) {
                        continue;
                    }

                    $classname = $locator->findQualifiedNameFromPath($file);

                    if ($classname === false) {
                        continue;
                    }

                    if ($classname !== Services::class) {
                        self::$serviceNames[] = $classname;
                    }
                }
            }

            static::$discovered = true;
        }
    }
}
