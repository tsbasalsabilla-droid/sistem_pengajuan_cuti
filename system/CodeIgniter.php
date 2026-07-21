<?php



namespace CodeIgniter;

use Closure;
use CodeIgniter\Cache\ResponseCache;
use CodeIgniter\Debug\Timer;
use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\LogicException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Filters\Filters;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Method;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponsableInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Router\RouteCollectionInterface;
use CodeIgniter\Router\Router;
use Config\App;
use Config\Cache;
use Config\Feature;
use Config\Kint as KintConfig;
use Config\Services;
use Exception;
use Kint;
use Kint\Renderer\CliRenderer;
use Kint\Renderer\RichRenderer;
use Locale;
use Throwable;


class CodeIgniter
{
    
    public const CI_VERSION = '4.7.2';

    
    protected $startTime;

    
    protected $totalTime;

    
    protected $config;

    
    protected $benchmark;

    
    protected $request;

    
    protected $response;

    
    protected $router;

    
    protected $controller;

    
    protected $method;

    
    protected $output;

    
    protected static $cacheTTL = 0;

    
    protected ?string $context = null;

    
    protected bool $enableFilters = true;

    
    protected bool $returnResponse = false;

    
    protected int $bufferLevel;

    
    protected ResponseCache $pageCache;

    
    public function __construct(App $config)
    {
        $this->startTime = microtime(true);
        $this->config    = $config;

        $this->pageCache = Services::responsecache();
    }

    
    public function initialize()
    {
        
        Locale::setDefault($this->config->defaultLocale ?? 'en');

        
        date_default_timezone_set($this->config->appTimezone ?? 'UTC');
    }

    
    public function resetForWorkerMode(): void
    {
        $this->request    = null;
        $this->response   = null;
        $this->router     = null;
        $this->controller = null;
        $this->method     = null;
        $this->output     = null;

        
        $this->startTime = null;
        $this->totalTime = 0;
    }

    
    protected function initializeKint()
    {
        if (CI_DEBUG) {
            $this->autoloadKint();
            $this->configureKint();
        } elseif (class_exists(Kint::class)) {
            
            Kint::$enabled_mode = false;
            
        }

        helper('kint');
    }

    
    private function autoloadKint(): void
    {
        
        if (! defined('KINT_DIR')) {
            spl_autoload_register(function ($class): void {
                $class = explode('\\', $class);

                if (array_shift($class) !== 'Kint') {
                    return;
                }

                $file = SYSTEMPATH . 'ThirdParty/Kint/' . implode('/', $class) . '.php';

                if (is_file($file)) {
                    require_once $file;
                }
            });

            require_once SYSTEMPATH . 'ThirdParty/Kint/init.php';
        }
    }

    
    private function configureKint(): void
    {
        $config = new KintConfig();

        Kint::$depth_limit         = $config->maxDepth;
        Kint::$display_called_from = $config->displayCalledFrom;
        Kint::$expanded            = $config->expanded;

        if (isset($config->plugins) && is_array($config->plugins)) {
            Kint::$plugins = $config->plugins;
        }

        $csp = Services::csp();
        if ($csp->enabled()) {
            RichRenderer::$js_nonce  = $csp->getScriptNonce();
            RichRenderer::$css_nonce = $csp->getStyleNonce();
        }

        RichRenderer::$theme  = $config->richTheme;
        RichRenderer::$folder = $config->richFolder;

        if (isset($config->richObjectPlugins) && is_array($config->richObjectPlugins)) {
            RichRenderer::$value_plugins = $config->richObjectPlugins;
        }
        if (isset($config->richTabPlugins) && is_array($config->richTabPlugins)) {
            RichRenderer::$tab_plugins = $config->richTabPlugins;
        }

        CliRenderer::$cli_colors         = $config->cliColors;
        CliRenderer::$force_utf8         = $config->cliForceUTF8;
        CliRenderer::$detect_width       = $config->cliDetectWidth;
        CliRenderer::$min_terminal_width = $config->cliMinWidth;
    }

    
    public function run(?RouteCollectionInterface $routes = null, bool $returnResponse = false)
    {
        if ($this->context === null) {
            throw new LogicException(
                'Context must be set before run() is called. If you are upgrading from 4.1.x, '
                . 'you need to merge `public/index.php` and `spark` file from `vendor/codeigniter4/framework`.',
            );
        }

        $this->pageCache->setTtl(0);
        $this->bufferLevel = ob_get_level();

        $this->startBenchmark();

        $this->getRequestObject();
        $this->getResponseObject();

        Events::trigger('pre_system');

        $this->benchmark->stop('bootstrap');

        $this->benchmark->start('required_before_filters');
        
        $filters = Services::filters();
        
        $possibleResponse = $this->runRequiredBeforeFilters($filters);

        
        if ($possibleResponse instanceof ResponseInterface) {
            $this->response = $possibleResponse;
        } else {
            try {
                $this->response = $this->handleRequest($routes, config(Cache::class), $returnResponse);
            } catch (ResponsableInterface $e) {
                $this->outputBufferingEnd();

                $this->response = $e->getResponse();
            } catch (PageNotFoundException $e) {
                $this->response = $this->display404errors($e);
            } catch (Throwable $e) {
                $this->outputBufferingEnd();

                throw $e;
            }
        }

        $this->runRequiredAfterFilters($filters);

        
        Events::trigger('post_system');

        if ($returnResponse) {
            return $this->response;
        }

        $this->sendResponse();

        return null;
    }

    
    private function runRequiredBeforeFilters(Filters $filters): ?ResponseInterface
    {
        $possibleResponse = $filters->runRequired('before');
        $this->benchmark->stop('required_before_filters');

        
        if ($possibleResponse instanceof ResponseInterface) {
            return $possibleResponse;
        }

        return null;
    }

    
    private function runRequiredAfterFilters(Filters $filters): void
    {
        $filters->setResponse($this->response);

        
        $this->benchmark->start('required_after_filters');
        $response = $filters->runRequired('after');
        $this->benchmark->stop('required_after_filters');

        if ($response instanceof ResponseInterface) {
            $this->response = $response;
        }
    }

    
    private function isPhpCli(): bool
    {
        return $this->context === 'php-cli';
    }

    
    private function isWeb(): bool
    {
        return $this->context === 'web';
    }

    
    public function disableFilters(): void
    {
        $this->enableFilters = false;
    }

    
    protected function handleRequest(?RouteCollectionInterface $routes, Cache $cacheConfig, bool $returnResponse = false)
    {
        if ($this->request instanceof IncomingRequest && $this->request->getMethod() === 'CLI') {
            return $this->response->setStatusCode(405)->setBody('Method Not Allowed');
        }

        $routeFilters = $this->tryToRouteIt($routes);

        
        $uri = $this->request->getPath();

        if ($this->enableFilters) {
            
            $filters = service('filters');

            
            
            if ($routeFilters !== null) {
                $filters->enableFilters($routeFilters, 'before');

                $oldFilterOrder = config(Feature::class)->oldFilterOrder ?? false;
                if (! $oldFilterOrder) {
                    $routeFilters = array_reverse($routeFilters);
                }

                $filters->enableFilters($routeFilters, 'after');
            }

            
            $this->benchmark->start('before_filters');
            $possibleResponse = $filters->run($uri, 'before');
            $this->benchmark->stop('before_filters');

            
            if ($possibleResponse instanceof ResponseInterface) {
                $this->outputBufferingEnd();

                return $possibleResponse;
            }

            if ($possibleResponse instanceof IncomingRequest || $possibleResponse instanceof CLIRequest) {
                $this->request = $possibleResponse;
            }
        }

        $returned = $this->startController();

        
        if ($returned instanceof ResponseInterface) {
            $this->gatherOutput($cacheConfig, $returned);
        }
        
        elseif (! is_callable($this->controller)) {
            $controller = $this->createController();

            if (! method_exists($controller, '_remap') && ! is_callable([$controller, $this->method], false)) {
                throw PageNotFoundException::forMethodNotFound($this->method);
            }

            
            Events::trigger('post_controller_constructor');

            $returned = $this->runController($controller);
        } else {
            $this->benchmark->stop('controller_constructor');
            $this->benchmark->stop('controller');
        }

        
        
        
        $this->gatherOutput($cacheConfig, $returned);

        if ($this->enableFilters) {
            
            $filters = service('filters');
            $filters->setResponse($this->response);

            
            $this->benchmark->start('after_filters');
            $response = $filters->run($uri, 'after');
            $this->benchmark->stop('after_filters');

            if ($response instanceof ResponseInterface) {
                $this->response = $response;
            }
        }

        
        if ((config('Routing')->useControllerAttributes ?? true) === true) {
            $this->benchmark->start('route_attributes_after');
            $this->response = $this->router->executeAfterAttributes($this->request, $this->response);
            $this->benchmark->stop('route_attributes_after');
        }

        
        if (
            ! $this->response instanceof DownloadResponse
            && ! $this->response instanceof RedirectResponse
        ) {
            
            
            $this->storePreviousURL(current_url(true));
        }

        unset($uri);

        return $this->response;
    }

    
    protected function detectEnvironment()
    {
        
        if (! defined('ENVIRONMENT')) {
            define('ENVIRONMENT', env('CI_ENVIRONMENT', 'production'));
        }
    }

    
    protected function bootstrapEnvironment()
    {
        if (is_file(APPPATH . 'Config/Boot/' . ENVIRONMENT . '.php')) {
            require_once APPPATH . 'Config/Boot/' . ENVIRONMENT . '.php';
        } else {
            
            header('HTTP/1.1 503 Service Unavailable.', true, 503);
            echo 'The application environment is not set correctly.';

            exit(EXIT_ERROR); 
            
        }
    }

    
    protected function startBenchmark()
    {
        if ($this->startTime === null) {
            $this->startTime = microtime(true);
        }

        $this->benchmark = Services::timer();
        $this->benchmark->start('total_execution', $this->startTime);
        $this->benchmark->start('bootstrap');
    }

    
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    
    protected function getRequestObject()
    {
        if ($this->request instanceof Request) {
            $this->spoofRequestMethod();

            return;
        }

        if ($this->isPhpCli()) {
            Services::createRequest($this->config, true);
        } else {
            Services::createRequest($this->config);
        }

        $this->request = service('request');

        $this->spoofRequestMethod();
    }

    
    protected function getResponseObject()
    {
        $this->response = Services::response($this->config);

        if ($this->isWeb()) {
            $this->response->setProtocolVersion($this->request->getProtocolVersion());
        }

        
        $this->response->setStatusCode(200);
    }

    
    protected function forceSecureAccess($duration = 31_536_000)
    {
        if ($this->config->forceGlobalSecureRequests !== true) {
            return;
        }

        force_https($duration, $this->request, $this->response);
    }

    
    public function displayCache(Cache $config)
    {
        $cachedResponse = $this->pageCache->get($this->request, $this->response);
        if ($cachedResponse instanceof ResponseInterface) {
            $this->response = $cachedResponse;

            $this->totalTime = $this->benchmark->getElapsedTime('total_execution');
            $output          = $this->displayPerformanceMetrics($cachedResponse->getBody());
            $this->response->setBody($output);

            return $this->response;
        }

        return false;
    }

    
    public static function cache(int $time)
    {
        static::$cacheTTL = $time;
    }

    
    public function cachePage(Cache $config)
    {
        $headers = [];

        foreach ($this->response->headers() as $header) {
            $headers[$header->getName()] = $header->getValueLine();
        }

        return cache()->save($this->generateCacheName($config), serialize(['headers' => $headers, 'output' => $this->output]), static::$cacheTTL);
    }

    
    public function getPerformanceStats(): array
    {
        
        $this->totalTime = $this->benchmark->getElapsedTime('total_execution');

        return [
            'startTime' => $this->startTime,
            'totalTime' => $this->totalTime,
        ];
    }

    
    protected function generateCacheName(Cache $config): string
    {
        if ($this->request instanceof CLIRequest) {
            return md5($this->request->getPath());
        }

        $uri = clone $this->request->getUri();

        $query = $config->cacheQueryString
            ? $uri->getQuery(is_array($config->cacheQueryString) ? ['only' => $config->cacheQueryString] : [])
            : '';

        return md5((string) $uri->setFragment('')->setQuery($query));
    }

    
    public function displayPerformanceMetrics(string $output): string
    {
        return str_replace(
            ['{elapsed_time}', '{memory_usage}'],
            [(string) $this->totalTime, number_format(memory_get_peak_usage() / 1024 / 1024, 3)],
            $output,
        );
    }

    
    protected function tryToRouteIt(?RouteCollectionInterface $routes = null)
    {
        $this->benchmark->start('routing');

        if (! $routes instanceof RouteCollectionInterface) {
            $routes = service('routes')->loadRoutes();
        }

        
        $this->router = Services::router($routes, $this->request);

        
        $uri = $this->request->getPath();

        $this->outputBufferingStart();

        $this->controller = $this->router->handle($uri);
        $this->method     = $this->router->methodName();

        
        
        if ($this->router->hasLocale()) {
            $this->request->setLocale($this->router->getLocale());
        }

        $this->benchmark->stop('routing');

        return $this->router->getFilters();
    }

    
    protected function determinePath()
    {
        return $this->request->getPath();
    }

    
    protected function startController()
    {
        $this->benchmark->start('controller');
        $this->benchmark->start('controller_constructor');

        
        if (is_object($this->controller) && ($this->controller::class === 'Closure')) {
            $controller = $this->controller;

            return $controller(...$this->router->params());
        }

        
        if (! isset($this->controller)) {
            throw PageNotFoundException::forEmptyController();
        }

        
        if (
            ! class_exists($this->controller, true)
            || ($this->method[0] === '_' && $this->method !== '__invoke')
        ) {
            throw PageNotFoundException::forControllerNotFound($this->controller, $this->method);
        }

        
        
        if ((config('Routing')->useControllerAttributes ?? true) === true) {
            $this->benchmark->start('route_attributes_before');
            $attributeResponse = $this->router->executeBeforeAttributes($this->request);
            $this->benchmark->stop('route_attributes_before');

            
            if ($attributeResponse instanceof ResponseInterface) {
                $this->benchmark->stop('controller_constructor');
                $this->benchmark->stop('controller');

                return $attributeResponse;
            }

            
            if ($attributeResponse instanceof RequestInterface) {
                $this->request = $attributeResponse;
            }
        }

        return null;
    }

    
    protected function createController()
    {
        assert(is_string($this->controller));

        $class = new $this->controller();
        $class->initController($this->request, $this->response, Services::logger());

        $this->benchmark->stop('controller_constructor');

        return $class;
    }

    
    protected function runController($class)
    {
        
        $params = $this->router->params();

        
        
        $output = method_exists($class, '_remap')
            ? $class->_remap($this->method, ...$params)
            : $class->{$this->method}(...$params);

        $this->benchmark->stop('controller');

        return $output;
    }

    
    protected function display404errors(PageNotFoundException $e)
    {
        $this->response->setStatusCode($e->getCode());

        
        $override = $this->router->get404Override();

        if ($override !== null) {
            $returned = null;

            if ($override instanceof Closure) {
                echo $override($e->getMessage());
            } elseif (is_array($override)) {
                $this->benchmark->start('controller');
                $this->benchmark->start('controller_constructor');

                $this->controller = $override[0];
                $this->method     = $override[1];

                $controller = $this->createController();

                $returned = $controller->{$this->method}($e->getMessage());

                $this->benchmark->stop('controller');
            }

            unset($override);

            $cacheConfig = config(Cache::class);
            $this->gatherOutput($cacheConfig, $returned);

            return $this->response;
        }

        $this->outputBufferingEnd();

        
        throw PageNotFoundException::forPageNotFound(
            (ENVIRONMENT !== 'production' || ! $this->isWeb()) ? $e->getMessage() : null,
        );
    }

    
    protected function gatherOutput(?Cache $cacheConfig = null, $returned = null)
    {
        $this->output = $this->outputBufferingEnd();

        if ($returned instanceof DownloadResponse) {
            $this->response = $returned;

            return;
        }
        
        
        
        
        
        
        if ($returned instanceof ResponseInterface) {
            $this->response = $returned;
            $returned       = $returned->getBody();
        }

        if (is_string($returned)) {
            $this->output .= $returned;
        }

        $this->response->setBody($this->output);
    }

    
    public function storePreviousURL($uri)
    {
        
        if (! $this->isWeb()) {
            return;
        }
        
        if (method_exists($this->request, 'isAJAX') && $this->request->isAJAX()) {
            return;
        }

        
        if ($this->response instanceof DownloadResponse || $this->response instanceof RedirectResponse) {
            return;
        }

        
        if (! str_contains($this->response->getHeaderLine('Content-Type'), 'text/html')) {
            return;
        }

        
        if (is_string($uri)) {
            $uri = new URI($uri);
        }

        if (isset($_SESSION)) {
            session()->set('_ci_previous_url', URI::createURIString(
                $uri->getScheme(),
                $uri->getAuthority(),
                $uri->getPath(),
                $uri->getQuery(),
                $uri->getFragment(),
            ));
        }
    }

    
    public function spoofRequestMethod()
    {
        
        if ($this->request->getMethod() !== Method::POST) {
            return;
        }

        $method = $this->request->getPost('_method');

        if ($method === null) {
            return;
        }

        
        if (in_array($method, [Method::PUT, Method::PATCH, Method::DELETE], true)) {
            $this->request = $this->request->setMethod($method);
        }
    }

    
    protected function sendResponse()
    {
        $this->response->send();
    }

    
    protected function callExit($code)
    {
        exit($code); 
    }

    
    public function setContext(string $context)
    {
        $this->context = $context;

        return $this;
    }

    protected function outputBufferingStart(): void
    {
        $this->bufferLevel = ob_get_level();
        ob_start();
    }

    protected function outputBufferingEnd(): string
    {
        $buffer = '';

        while (ob_get_level() > $this->bufferLevel) {
            $buffer .= ob_get_contents();
            ob_end_clean();
        }

        return $buffer;
    }
}
