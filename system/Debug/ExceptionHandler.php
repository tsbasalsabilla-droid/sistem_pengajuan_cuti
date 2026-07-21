<?php

declare(strict_types=1);



namespace CodeIgniter\Debug;

use Closure;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Paths;
use Throwable;


final class ExceptionHandler extends BaseExceptionHandler implements ExceptionHandlerInterface
{
    use ResponseTrait;

    
    private ?RequestInterface $request = null;

    
    private ?ResponseInterface $response = null;

    
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        
        $this->request  = $request;
        $this->response = $response;

        if ($request instanceof IncomingRequest) {
            try {
                $response->setStatusCode($statusCode);
            } catch (HTTPException) {
                
                $statusCode = 500;
                $response->setStatusCode($statusCode);
            }

            if (! headers_sent()) {
                header(
                    sprintf(
                        'HTTP/%s %s %s',
                        $request->getProtocolVersion(),
                        $response->getStatusCode(),
                        $response->getReasonPhrase(),
                    ),
                    true,
                    $statusCode,
                );
            }

            
            if (! str_contains($request->getHeaderLine('accept'), 'text/html')) {
                
                $data = $this->isDisplayErrorsEnabled()
                    ? $this->collectVars($exception, $statusCode)
                    : '';

                
                
                if ($data !== '') {
                    $data = $this->sanitizeData($data);
                }

                $this->respond($data, $statusCode)->send();

                if (ENVIRONMENT !== 'testing') {
                    
                    exit($exitCode);
                    
                }

                return;
            }
        }

        
        $addPath = ($request instanceof IncomingRequest ? 'html' : 'cli') . DIRECTORY_SEPARATOR;
        $path    = $this->viewPath . $addPath;
        $altPath = rtrim((new Paths())->viewDirectory, '\\/ ')
            . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . $addPath;

        
        $view    = $this->determineView($exception, $path, $statusCode);
        $altView = $this->determineView($exception, $altPath, $statusCode);

        
        $viewFile = null;
        if (is_file($path . $view)) {
            $viewFile = $path . $view;
        } elseif (is_file($altPath . $altView)) {
            $viewFile = $altPath . $altView;
        }

        
        $this->render($exception, $statusCode, $viewFile);

        if (ENVIRONMENT !== 'testing') {
            
            exit($exitCode);
            
        }
    }

    
    protected function determineView(
        Throwable $exception,
        string $templatePath,
        int $statusCode = 500,
    ): string {
        
        $view = 'production.php';

        if ($this->isDisplayErrorsEnabled()) {
            
            $view = 'error_exception.php';
        }

        
        if ($exception instanceof PageNotFoundException) {
            return 'error_404.php';
        }

        $templatePath = rtrim($templatePath, '\\/ ') . DIRECTORY_SEPARATOR;

        
        if (is_file($templatePath . 'error_' . $statusCode . '.php')) {
            return 'error_' . $statusCode . '.php';
        }

        return $view;
    }

    private function isDisplayErrorsEnabled(): bool
    {
        return in_array(
            strtolower(ini_get('display_errors')),
            ['1', 'true', 'on', 'yes'],
            true,
        );
    }

    
    private function sanitizeData(mixed $data, array &$seen = []): mixed
    {
        $type = gettype($data);

        switch ($type) {
            case 'resource':
            case 'resource (closed)':
                return '[Resource #' . (int) $data . ']';

            case 'array':
                $result = [];

                foreach ($data as $key => $value) {
                    $result[$key] = $this->sanitizeData($value, $seen);
                }

                return $result;

            case 'object':
                $oid = spl_object_id($data);
                if (isset($seen[$oid])) {
                    return '[' . $data::class . ' Object *RECURSION*]';
                }
                $seen[$oid] = true;

                if ($data instanceof Closure) {
                    return '[Closure]';
                }

                $result = [];

                foreach ((array) $data as $key => $value) {
                    $cleanKey          = preg_replace('/^\x00.*\x00/', '', (string) $key);
                    $result[$cleanKey] = $this->sanitizeData($value, $seen);
                }

                return $result;

            default:
                return $data;
        }
    }
}
