<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP\Exceptions;

use CodeIgniter\Exceptions\HTTPExceptionInterface;
use CodeIgniter\Exceptions\RuntimeException;


class BadRequestException extends RuntimeException implements HTTPExceptionInterface
{
    
    protected $code = 400; 
}
