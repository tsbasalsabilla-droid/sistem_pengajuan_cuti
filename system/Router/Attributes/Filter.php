<?php

declare(strict_types=1);



namespace CodeIgniter\Router\Attributes;

use Attribute;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Filter implements RouteAttributeInterface
{
    public function __construct(
        public string $by,
        public array $having = [],
    ) {
    }

    public function before(RequestInterface $request): RequestInterface|ResponseInterface|null
    {
        
        
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response): ?ResponseInterface
    {
        return null;
    }

    public function getFilters(): array
    {
        if ($this->having === []) {
            return [$this->by];
        }

        return [$this->by . ':' . implode(',', $this->having)];
    }
}
