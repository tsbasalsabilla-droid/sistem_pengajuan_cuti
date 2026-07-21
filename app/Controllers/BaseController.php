<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\PengajuanCutiModel;


abstract class BaseController extends Controller
{
    

    
    protected $session;
    protected $user;

    
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        
        
        

        
        parent::initController($request, $response, $logger);

        $this->session = session();
        $this->session = service('session');
        $this->user = $this->session->get('user') ?? null;

        (new PengajuanCutiModel())->updateBatalOtomatis();
    }
}
