<?php

declare(strict_types=1);



namespace CodeIgniter\Config;

use CodeIgniter\Cache\CacheFactory;
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
use CodeIgniter\Encryption\Encryption;
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
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\SiteURIFactory;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Images\Handlers\BaseHandler;
use CodeIgniter\Language\Language;
use CodeIgniter\Log\Logger;
use CodeIgniter\Pager\Pager;
use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Router\RouteCollectionInterface;
use CodeIgniter\Router\Router;
use CodeIgniter\Security\Security;
use CodeIgniter\Session\Handlers\BaseHandler as SessionBaseHandler;
use CodeIgniter\Session\Handlers\Database\MySQLiHandler;
use CodeIgniter\Session\Handlers\Database\PostgreHandler;
use CodeIgniter\Session\Handlers\DatabaseHandler;
use CodeIgniter\Session\Session;
use CodeIgniter\Superglobals;
use CodeIgniter\Throttle\Throttler;
use CodeIgniter\Typography\Typography;
use CodeIgniter\Validation\Validation;
use CodeIgniter\Validation\ValidationInterface;
use CodeIgniter\View\Cell;
use CodeIgniter\View\Parser;
use CodeIgniter\View\RendererInterface;
use CodeIgniter\View\View;
use Config\App;
use Config\Cache;
use Config\ContentSecurityPolicy as ContentSecurityPolicyConfig;
use Config\ContentSecurityPolicy as CSPConfig;
use Config\Database;
use Config\Email as EmailConfig;
use Config\Encryption as EncryptionConfig;
use Config\Exceptions as ExceptionsConfig;
use Config\Filters as FiltersConfig;
use Config\Format as FormatConfig;
use Config\Honeypot as HoneypotConfig;
use Config\Images;
use Config\Logger as LoggerConfig;
use Config\Migrations;
use Config\Modules;
use Config\Pager as PagerConfig;
use Config\Paths;
use Config\Routing;
use Config\Security as SecurityConfig;
use Config\Services as AppServices;
use Config\Session as SessionConfig;
use Config\Toolbar as ToolbarConfig;
use Config\Validation as ValidationConfig;
use Config\View as ViewConfig;
use InvalidArgumentException;
use Locale;


class Services extends BaseService
{
    
    public static function cache(?Cache $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('cache', $config);
        }

        $config ??= config(Cache::class);

        return CacheFactory::getHandler($config);
    }

    
    public static function clirequest(?App $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('clirequest', $config);
        }

        $config ??= config(App::class);

        return new CLIRequest($config);
    }

    
    public static function codeigniter(?App $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('codeigniter', $config);
        }

        $config ??= config(App::class);

        return new CodeIgniter($config);
    }

    
    public static function commands(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('commands');
        }

        return new Commands();
    }

    
    public static function csp(?CSPConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('csp', $config);
        }

        $config ??= config(ContentSecurityPolicyConfig::class);

        return new ContentSecurityPolicy($config);
    }

    
    public static function curlrequest(array $options = [], ?ResponseInterface $response = null, ?App $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('curlrequest', $options, $response, $config);
        }

        $config ??= config(App::class);
        $response ??= new Response($config);

        return new CURLRequest(
            $config,
            new URI($options['baseURI'] ?? null),
            $response,
            $options,
        );
    }

    
    public static function email($config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('email', $config);
        }

        if (empty($config) || (! is_array($config) && ! $config instanceof EmailConfig)) {
            $config = config(EmailConfig::class);
        }

        return new Email($config);
    }

    
    public static function encrypter(?EncryptionConfig $config = null, $getShared = false)
    {
        if ($getShared === true) {
            return static::getSharedInstance('encrypter', $config);
        }

        $config ??= config(EncryptionConfig::class);
        $encryption = new Encryption($config);

        return $encryption->initialize($config);
    }

    
    public static function exceptions(
        ?ExceptionsConfig $config = null,
        bool $getShared = true,
    ) {
        if ($getShared) {
            return static::getSharedInstance('exceptions', $config);
        }

        $config ??= config(ExceptionsConfig::class);

        return new Exceptions($config);
    }

    
    public static function filters(?FiltersConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('filters', $config);
        }

        $config ??= config(FiltersConfig::class);

        return new Filters($config, AppServices::get('request'), AppServices::get('response'));
    }

    
    public static function format(?FormatConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('format', $config);
        }

        $config ??= config(FormatConfig::class);

        return new Format($config);
    }

    
    public static function honeypot(?HoneypotConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('honeypot', $config);
        }

        $config ??= config(HoneypotConfig::class);

        return new Honeypot($config);
    }

    
    public static function image(?string $handler = null, ?Images $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('image', $handler, $config);
        }

        $config ??= config(Images::class);
        assert($config instanceof Images);

        $handler = in_array($handler, [null, '', '0'], true) ? $config->defaultHandler : $handler;
        $class   = $config->handlers[$handler];

        return new $class($config);
    }

    
    public static function iterator(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('iterator');
        }

        return new Iterator();
    }

    
    public static function language(?string $locale = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('language', $locale)->setLocale($locale);
        }

        if (AppServices::get('request') instanceof IncomingRequest) {
            $requestLocale = AppServices::get('request')->getLocale();
        } else {
            $requestLocale = Locale::getDefault();
        }

        
        $locale = in_array($locale, [null, '', '0'], true) ? $requestLocale : $locale;

        return new Language($locale);
    }

    
    public static function logger(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('logger');
        }

        return new Logger(config(LoggerConfig::class));
    }

    
    public static function migrations(?Migrations $config = null, ?ConnectionInterface $db = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('migrations', $config, $db);
        }

        $config ??= config(Migrations::class);

        return new MigrationRunner($config, $db);
    }

    
    public static function negotiator(?RequestInterface $request = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('negotiator', $request);
        }

        $request ??= AppServices::get('request');

        return new Negotiate($request);
    }

    
    public static function responsecache(?Cache $config = null, ?CacheInterface $cache = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('responsecache', $config, $cache);
        }

        $config ??= config(Cache::class);
        $cache ??= AppServices::get('cache');

        return new ResponseCache($config, $cache);
    }

    
    public static function pager(?PagerConfig $config = null, ?RendererInterface $view = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('pager', $config, $view);
        }

        $config ??= config(PagerConfig::class);
        $view ??= AppServices::renderer(null, null, false);

        return new Pager($config, $view);
    }

    
    public static function parser(?string $viewPath = null, ?ViewConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('parser', $viewPath, $config);
        }

        $viewPath = in_array($viewPath, [null, '', '0'], true) ? (new Paths())->viewDirectory : $viewPath;
        $config ??= config(ViewConfig::class);

        return new Parser($config, $viewPath, AppServices::get('locator'), CI_DEBUG, AppServices::get('logger'));
    }

    
    public static function renderer(?string $viewPath = null, ?ViewConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('renderer', $viewPath, $config);
        }

        $viewPath = in_array($viewPath, [null, '', '0'], true) ? (new Paths())->viewDirectory : $viewPath;
        $config ??= config(ViewConfig::class);

        return new View($config, $viewPath, AppServices::get('locator'), CI_DEBUG, AppServices::get('logger'));
    }

    
    public static function request(?App $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('request', $config);
        }

        
        return AppServices::incomingrequest($config, $getShared);
    }

    
    public static function createRequest(App $config, bool $isCli = false): void
    {
        if ($isCli) {
            $request = AppServices::clirequest($config);
        } else {
            $request = AppServices::incomingrequest($config);

            
            $request->setProtocolVersion(static::superglobals()->server('SERVER_PROTOCOL', 'HTTP/1.1'));
        }

        
        static::$instances['request'] = $request;
    }

    
    public static function incomingrequest(?App $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('request', $config);
        }

        $config ??= config(App::class);

        return new IncomingRequest(
            $config,
            AppServices::get('uri'),
            'php://input',
            new UserAgent(),
        );
    }

    
    public static function response(?App $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('response', $config);
        }

        $config ??= config(App::class);

        return new Response($config);
    }

    
    public static function redirectresponse(?App $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('redirectresponse', $config);
        }

        $config ??= config(App::class);
        $response = new RedirectResponse($config);
        $response->setProtocolVersion(AppServices::get('request')->getProtocolVersion());

        return $response;
    }

    
    public static function routes(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('routes');
        }

        return new RouteCollection(AppServices::get('locator'), new Modules(), config(Routing::class));
    }

    
    public static function router(?RouteCollectionInterface $routes = null, ?Request $request = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('router', $routes, $request);
        }

        $routes ??= AppServices::get('routes');
        $request ??= AppServices::get('request');

        return new Router($routes, $request);
    }

    
    public static function security(?SecurityConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('security', $config);
        }

        $config ??= config(SecurityConfig::class);

        return new Security($config);
    }

    
    public static function session(?SessionConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('session', $config);
        }

        $config ??= config(SessionConfig::class);

        $logger = AppServices::get('logger');

        $driverName = $config->driver;

        if ($driverName === DatabaseHandler::class) {
            $DBGroup = $config->DBGroup ?? config(Database::class)->defaultGroup;

            $driverPlatform = Database::connect($DBGroup)->getPlatform();

            if ($driverPlatform === 'MySQLi') {
                $driverName = MySQLiHandler::class;
            } elseif ($driverPlatform === 'Postgre') {
                $driverName = PostgreHandler::class;
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Invalid session database handler "%s" provided. Only "MySQLi" and "Postgre" are supported.',
                    $driverPlatform,
                ));
            }
        }

        if (! class_exists($driverName) || ! is_a($driverName, SessionBaseHandler::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid session handler "%s" provided.',
                $driverName,
            ));
        }

        
        $driver = new $driverName($config, AppServices::get('request')->getIPAddress());
        $driver->setLogger($logger);

        $session = new Session($driver, $config);
        $session->setLogger($logger);

        if (session_status() === PHP_SESSION_NONE) {
            
            
            
            
            AppServices::get('response')->removeHeader('Cache-Control');

            $session->start();
        }

        return $session;
    }

    
    public static function siteurifactory(
        ?App $config = null,
        ?Superglobals $superglobals = null,
        bool $getShared = true,
    ) {
        if ($getShared) {
            return static::getSharedInstance('siteurifactory', $config, $superglobals);
        }

        $config ??= config('App');
        $superglobals ??= AppServices::get('superglobals');

        return new SiteURIFactory($config, $superglobals);
    }

    
    public static function superglobals(
        ?array $server = null,
        ?array $get = null,
        ?array $post = null,
        ?array $cookie = null,
        ?array $files = null,
        ?array $request = null,
        bool $getShared = true,
    ) {
        if ($getShared) {
            return static::getSharedInstance('superglobals', $server, $get, $post, $cookie, $files, $request);
        }

        return new Superglobals($server, $get, $post, $cookie, $files, $request);
    }

    
    public static function throttler(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('throttler');
        }

        return new Throttler(AppServices::get('cache'));
    }

    
    public static function timer(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('timer');
        }

        return new Timer();
    }

    
    public static function toolbar(?ToolbarConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('toolbar', $config);
        }

        $config ??= config(ToolbarConfig::class);

        return new Toolbar($config);
    }

    
    public static function uri(?string $uri = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('uri', $uri);
        }

        if ($uri === null) {
            $appConfig = config(App::class);
            $factory   = AppServices::siteurifactory($appConfig, AppServices::get('superglobals'));

            return $factory->createFromGlobals();
        }

        return new URI($uri);
    }

    
    public static function validation(?ValidationConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('validation', $config);
        }

        $config ??= config(ValidationConfig::class);

        return new Validation($config, AppServices::get('renderer'));
    }

    
    public static function viewcell(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('viewcell');
        }

        return new Cell(AppServices::get('cache'));
    }

    
    public static function typography(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('typography');
        }

        return new Typography();
    }
}
