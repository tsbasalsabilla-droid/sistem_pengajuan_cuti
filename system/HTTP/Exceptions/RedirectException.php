<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP\Exceptions;

use CodeIgniter\Exceptions\HTTPExceptionInterface;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Exceptions\LogicException;
use CodeIgniter\Exceptions\RuntimeException;
use CodeIgniter\HTTP\ResponsableInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;


class RedirectException extends RuntimeException implements ExceptionInterface, ResponsableInterface, HTTPExceptionInterface
{
    
    protected $code = 302;

    protected ?ResponseInterface $response = null;

    
    public function __construct($message = '', int $code = 0, ?Throwable $previous = null)
    {
        if (! is_string($message) && ! $message instanceof ResponseInterface) {
            throw new InvalidArgumentException(
                'RedirectException::__construct() first argument must be a string or ResponseInterface',
                0,
                $this,
            );
        }

        if ($message instanceof ResponseInterface) {
            $this->response = $message;

            $message = '';

            if ($this->response->getHeaderLine('Location') === '' && $this->response->getHeaderLine('Refresh') === '') {
                throw new LogicException(
                    'The Response object passed to RedirectException does not contain a redirect address.',
                );
            }

            if ($this->response->getStatusCode() < 301 || $this->response->getStatusCode() > 308) {
                $this->response->setStatusCode($this->code);
            }
        }

        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): ResponseInterface
    {
        if (! $this->response instanceof ResponseInterface) {
            $this->response = service('response')->redirect(
                base_url($this->getMessage()),
                'auto',
                $this->getCode(),
            );
        }

        $location = $this->response->getHeaderLine('Location');

        service('logger')->info(sprintf(
            'REDIRECTED ROUTE at %s',
            $location !== '' ? $location : substr($this->response->getHeaderLine('Refresh'), 6),
        ));

        return $this->response;
    }
}
