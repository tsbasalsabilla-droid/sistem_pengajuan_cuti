<?php

declare(strict_types=1);



namespace CodeIgniter\API;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Format\Format;
use CodeIgniter\Format\FormatterInterface;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Model;
use Throwable;


trait ResponseTrait
{
    
    protected $codes = [
        'created'                   => 201,
        'deleted'                   => 200,
        'updated'                   => 200,
        'no_content'                => 204,
        'invalid_request'           => 400,
        'unsupported_response_type' => 400,
        'invalid_scope'             => 400,
        'temporarily_unavailable'   => 400,
        'invalid_grant'             => 400,
        'invalid_credentials'       => 400,
        'invalid_refresh'           => 400,
        'no_data'                   => 400,
        'invalid_data'              => 400,
        'access_denied'             => 401,
        'unauthorized'              => 401,
        'invalid_client'            => 401,
        'forbidden'                 => 403,
        'resource_not_found'        => 404,
        'not_acceptable'            => 406,
        'resource_exists'           => 409,
        'conflict'                  => 409,
        'resource_gone'             => 410,
        'payload_too_large'         => 413,
        'unsupported_media_type'    => 415,
        'too_many_requests'         => 429,
        'server_error'              => 500,
        'unsupported_grant_type'    => 501,
        'not_implemented'           => 501,
    ];

    
    protected $format = 'json';

    
    protected $formatter;

    
    protected function respond($data = null, ?int $status = null, string $message = '')
    {
        if ($data === null && $status === null) {
            $status = 404;
            $output = null;
            $this->format($data);
        } elseif ($data === null && is_numeric($status)) {
            $output = null;
            $this->format($data);
        } else {
            $status ??= 200;
            $output = $this->format($data);
        }

        if ($output !== null) {
            if ($this->format === 'json') {
                return $this->response->setJSON($output)->setStatusCode($status, $message);
            }

            if ($this->format === 'xml') {
                return $this->response->setXML($output)->setStatusCode($status, $message);
            }
        }

        return $this->response->setBody($output)->setStatusCode($status, $message);
    }

    
    protected function fail($messages, int $status = 400, ?string $code = null, string $customMessage = '')
    {
        if (! is_array($messages)) {
            $messages = ['error' => $messages];
        }

        $response = [
            'status'   => $status,
            'error'    => $code ?? $status,
            'messages' => $messages,
        ];

        return $this->respond($response, $status, $customMessage);
    }

    
    
    

    
    protected function respondCreated($data = null, string $message = '')
    {
        return $this->respond($data, $this->codes['created'], $message);
    }

    
    protected function respondDeleted($data = null, string $message = '')
    {
        return $this->respond($data, $this->codes['deleted'], $message);
    }

    
    protected function respondUpdated($data = null, string $message = '')
    {
        return $this->respond($data, $this->codes['updated'], $message);
    }

    
    protected function respondNoContent(string $message = 'No Content')
    {
        return $this->respond(null, $this->codes['no_content'], $message);
    }

    
    protected function failUnauthorized(string $description = 'Unauthorized', ?string $code = null, string $message = '')
    {
        return $this->fail($description, $this->codes['unauthorized'], $code, $message);
    }

    
    protected function failForbidden(string $description = 'Forbidden', ?string $code = null, string $message = '')
    {
        return $this->fail($description, $this->codes['forbidden'], $code, $message);
    }

    
    protected function failNotFound(string $description = 'Not Found', ?string $code = null, string $message = '')
    {
        return $this->fail($description, $this->codes['resource_not_found'], $code, $message);
    }

    
    protected function failValidationErrors($errors, ?string $code = null, string $message = '')
    {
        return $this->fail($errors, $this->codes['invalid_data'], $code, $message);
    }

    
    protected function failResourceExists(string $description = 'Conflict', ?string $code = null, string $message = '')
    {
        return $this->fail($description, $this->codes['resource_exists'], $code, $message);
    }

    
    protected function failResourceGone(string $description = 'Gone', ?string $code = null, string $message = '')
    {
        return $this->fail($description, $this->codes['resource_gone'], $code, $message);
    }

    
    protected function failTooManyRequests(string $description = 'Too Many Requests', ?string $code = null, string $message = '')
    {
        return $this->fail($description, $this->codes['too_many_requests'], $code, $message);
    }

    
    protected function failServerError(string $description = 'Internal Server Error', ?string $code = null, string $message = ''): ResponseInterface
    {
        return $this->fail($description, $this->codes['server_error'], $code, $message);
    }

    
    
    

    
    protected function format($data = null)
    {
        
        $format = service('format');

        $mime = $this->format === null
            ? $format->getConfig()->supportedResponseFormats[0]
            : "application/{$this->format}";

        
        if (
            ! in_array($this->format, ['json', 'xml'], true)
            && $this->request instanceof IncomingRequest
        ) {
            $mime = $this->request->negotiate(
                'media',
                $format->getConfig()->supportedResponseFormats,
                false,
            );
        }

        $this->response->setContentType($mime);

        
        $this->formatter ??= $format->getFormatter($mime);

        $asHtml = property_exists($this, 'stringAsHtml') ? $this->stringAsHtml : false;

        if (
            ($mime === 'application/json' && $asHtml && is_string($data))
            || ($mime !== 'application/json' && is_string($data))
        ) {
            
            $contentType = $this->response->getHeaderLine('Content-Type');
            $contentType = str_replace('application/json', 'text/html', $contentType);
            $contentType = str_replace('application/', 'text/', $contentType);
            $this->response->setContentType($contentType);
            $this->format = 'html';

            return $data;
        }

        if ($mime !== 'application/json') {
            
            
            
            $data = json_decode(json_encode($data), true);
        }

        return $this->formatter->format($data);
    }

    
    protected function setResponseFormat(?string $format = null)
    {
        $this->format = $format === null ? null : strtolower($format);

        return $this;
    }

    
    
    

    
    protected function paginate(BaseBuilder|Model $resource, int $perPage = 20, ?string $transformWith = null): ResponseInterface
    {
        try {
            assert($this->request instanceof IncomingRequest);

            $page = max(1, (int) ($this->request->getGet('page') ?? 1));

            
            if ($resource instanceof Model) {
                $data  = $resource->paginate($perPage, 'default', $page);
                $pager = $resource->pager;

                $meta = [
                    'page'       => $pager->getCurrentPage(),
                    'perPage'    => $pager->getPerPage(),
                    'total'      => $pager->getTotal(),
                    'totalPages' => $pager->getPageCount(),
                ];
            } else {
                
                $offset = ($page - 1) * $perPage;
                $total  = (clone $resource)->countAllResults();
                $data   = $resource->limit($perPage, $offset)->get()->getResultArray();

                $meta = [
                    'page'       => $page,
                    'perPage'    => $perPage,
                    'total'      => $total,
                    'totalPages' => (int) ceil($total / $perPage),
                ];
            }

            
            if ($transformWith !== null) {
                if (! class_exists($transformWith)) {
                    throw ApiException::forTransformerNotFound($transformWith);
                }

                $transformer = new $transformWith($this->request);

                if (! $transformer instanceof TransformerInterface) {
                    throw ApiException::forInvalidTransformer($transformWith);
                }

                $data = $transformer->transformMany($data);
            }

            $links = $this->buildLinks($meta);

            $this->response->setHeader('Link', $this->linkHeader($links));
            $this->response->setHeader('X-Total-Count', (string) $meta['total']);

            return $this->respond([
                'data'  => $data,
                'meta'  => $meta,
                'links' => $links,
            ]);
        } catch (ApiException $e) {
            
            throw $e;
        } catch (DatabaseException $e) {
            log_message('error', lang('RESTful.cannotPaginate') . ' ' . $e->getMessage());

            return $this->failServerError(lang('RESTful.cannotPaginate'));
        } catch (Throwable $e) {
            log_message('error', lang('RESTful.paginateError') . ' ' . $e->getMessage());

            return $this->failServerError(lang('RESTful.paginateError'));
        }
    }

    
    private function buildLinks(array $meta): array
    {
        assert($this->request instanceof IncomingRequest);

        
        $uri   = current_url(true);
        $query = $this->request->getGet();

        $set = static function ($page) use ($uri, $query, $meta): string {
            $params         = $query;
            $params['page'] = $page;

            
            if (! isset($params['perPage']) && $meta['perPage'] !== 20) {
                $params['perPage'] = $meta['perPage'];
            }

            return (string) (new URI((string) $uri))->setQuery(http_build_query($params));
        };

        $totalPages = max(1, (int) $meta['totalPages']);
        $page       = (int) $meta['page'];

        return [
            'self'  => $set($page),
            'first' => $set(1),
            'last'  => $set($totalPages),
            'prev'  => $page > 1 ? $set($page - 1) : null,
            'next'  => $page < $totalPages ? $set($page + 1) : null,
        ];
    }

    
    private function linkHeader(array $links): string
    {
        $parts = [];

        foreach (['self', 'first', 'prev', 'next', 'last'] as $rel) {
            if ($links[$rel] !== null && $links[$rel] !== '') {
                $parts[] = "<{$links[$rel]}>; rel=\"{$rel}\"";
            }
        }

        return implode(', ', $parts);
    }
}
