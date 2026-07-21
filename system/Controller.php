<?php

declare(strict_types=1);



namespace CodeIgniter;

use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Validation\Exceptions\ValidationException;
use CodeIgniter\Validation\ValidationInterface;
use Config\Validation;
use Psr\Log\LoggerInterface;


class Controller
{
    
    protected $helpers = [];

    
    protected $request;

    
    protected $response;

    
    protected $logger;

    
    protected $forceHTTPS = 0;

    
    protected $validator;

    
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        $this->request  = $request;
        $this->response = $response;
        $this->logger   = $logger;

        if ($this->forceHTTPS > 0) {
            $this->forceHTTPS($this->forceHTTPS);
        }

        
        helper($this->helpers);
    }

    
    protected function forceHTTPS(int $duration = 31_536_000)
    {
        force_https($duration, $this->request, $this->response);
    }

    
    protected function cachePage(int $time)
    {
        service('responsecache')->setTtl($time);
    }

    
    protected function validate($rules, array $messages = []): bool
    {
        $this->setValidator($rules, $messages);

        return $this->validator->withRequest($this->request)->run();
    }

    
    protected function validateData(array $data, $rules, array $messages = [], ?string $dbGroup = null): bool
    {
        $this->setValidator($rules, $messages);

        return $this->validator->run($data, null, $dbGroup);
    }

    
    private function setValidator($rules, array $messages): void
    {
        $this->validator = service('validation');

        
        if (is_string($rules)) {
            $validation = config(Validation::class);

            
            
            if (! isset($validation->{$rules})) {
                throw ValidationException::forRuleNotFound($rules);
            }

            
            if ($messages === []) {
                $errorName = $rules . '_errors';
                $messages  = $validation->{$errorName} ?? [];
            }

            $rules = $validation->{$rules};
        }

        $this->validator->setRules($rules, $messages);
    }
}
