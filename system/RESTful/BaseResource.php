<?php

declare(strict_types=1);



namespace CodeIgniter\RESTful;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseResource extends Controller
{
    
    protected $request;

    
    protected $modelName;

    
    protected $model;

    
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->setModel($this->modelName);
    }

    
    public function setModel($which = null)
    {
        if ($which !== null) {
            $this->model     = is_object($which) ? $which : null;
            $this->modelName = is_object($which) ? null : $which;
        }

        if (empty($this->model) && ! empty($this->modelName) && class_exists($this->modelName)) {
            $this->model = model($this->modelName);
        }

        if (! empty($this->model) && empty($this->modelName)) {
            $this->modelName = $this->model::class;
        }
    }
}
