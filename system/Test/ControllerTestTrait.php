<?php



namespace CodeIgniter\Test;

use CodeIgniter\Controller;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\URI;
use Config\App;
use Config\Services;
use Psr\Log\LoggerInterface;
use Throwable;


trait ControllerTestTrait
{
    
    protected $appConfig;

    
    protected $request;

    
    protected $response;

    
    protected $logger;

    
    protected $controller;

    
    protected $uri = 'http://example.com';

    
    protected $body;

    
    protected function setUpControllerTestTrait(): void
    {
        
        helper('url');

        if (! $this->appConfig instanceof App) {
            $this->appConfig = config(App::class);
        }

        if (! $this->uri instanceof URI) {
            $factory   = Services::siteurifactory($this->appConfig, service('superglobals'), false);
            $this->uri = $factory->createFromGlobals();
        }

        if (! $this->request instanceof IncomingRequest) {
            
            $tempUri = service('uri');
            Services::injectMock('uri', $this->uri);

            $this->withRequest(service('incomingrequest', $this->appConfig, false));

            
            Services::injectMock('uri', $tempUri);
        }

        if (! $this->response instanceof ResponseInterface) {
            $this->response = service('response', $this->appConfig, false);
        }

        if (! $this->logger instanceof LoggerInterface) {
            $this->logger = service('logger');
        }
    }

    
    public function controller(string $name)
    {
        if (! class_exists($name)) {
            throw new InvalidArgumentException('Invalid Controller: ' . $name);
        }

        $this->controller = new $name();
        $this->controller->initController($this->request, $this->response, $this->logger);

        return $this;
    }

    
    public function execute(string $method, ...$params)
    {
        if (! method_exists($this->controller, $method) || ! is_callable([$this->controller, $method])) {
            throw new InvalidArgumentException('Method does not exist or is not callable in controller: ' . $method);
        }

        $response = null;
        $this->request->setBody($this->body);

        try {
            ob_start();
            
            
            $response = $this->controller->{$method}(...$params);
        } catch (Throwable $e) {
            $code = $e->getCode();

            
            if ($code < 100 || $code >= 600) {
                throw $e;
            }
        } finally {
            $output = ob_get_clean();
        }

        
        if (is_string($response)) {
            $output = is_string($output) ? $output . $response : $response;
        }

        
        if (! $response instanceof ResponseInterface) {
            $response = $this->response;
        }

        
        
        if (is_string($output)) {
            if (is_string($response->getBody())) {
                $response->setBody($output . $response->getBody());
            } else {
                $response->setBody($output);
            }
        }

        
        if (isset($code)) {
            $response->setStatusCode($code);
        }
        
        else {
            
            try {
                $response->getStatusCode();
            } catch (HTTPException) {
                
                $response->setStatusCode(200);
            }
        }

        
        return (new TestResponse($response))->setRequest($this->request);
    }

    
    public function withConfig($appConfig)
    {
        $this->appConfig = $appConfig;

        return $this;
    }

    
    public function withRequest($request)
    {
        $this->request = $request;

        
        Services::injectMock('request', $request);

        return $this;
    }

    
    public function withResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    
    public function withLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    
    public function withUri(string $uri)
    {
        $factory   = service('siteurifactory');
        $this->uri = $factory->createFromString($uri);
        Services::injectMock('uri', $this->uri);

        
        $this->request = service('incomingrequest', null, false);
        Services::injectMock('request', $this->request);

        return $this;
    }

    
    public function withBody($body)
    {
        $this->body = $body;

        return $this;
    }
}
