<?php

declare(strict_types=1);



namespace CodeIgniter\Pager;

use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Pager\Exceptions\PagerException;
use CodeIgniter\View\RendererInterface;
use Config\Pager as PagerConfig;


class Pager implements PagerInterface
{
    
    protected $groups = [];

    
    protected $segment = [];

    
    protected $config;

    
    protected $view;

    
    protected $only;

    
    public function __construct(PagerConfig $config, RendererInterface $view)
    {
        $this->config = $config;
        $this->view   = $view;
    }

    
    public function links(string $group = 'default', string $template = 'default_full'): string
    {
        $this->ensureGroup($group);

        return $this->displayLinks($group, $template);
    }

    
    public function simpleLinks(string $group = 'default', string $template = 'default_simple'): string
    {
        $this->ensureGroup($group);

        return $this->displayLinks($group, $template);
    }

    
    public function makeLinks(int $page, ?int $perPage, int $total, string $template = 'default_full', int $segment = 0, ?string $group = 'default'): string
    {
        $group = $group === '' ? 'default' : $group;

        $this->store($group, $page, $perPage ?? $this->config->perPage, $total, $segment);

        return $this->displayLinks($group, $template);
    }

    
    protected function displayLinks(string $group, string $template): string
    {
        if (! array_key_exists($template, $this->config->templates)) {
            throw PagerException::forInvalidTemplate($template);
        }

        $pager = new PagerRenderer($this->getDetails($group));

        return $this->view->setVar('pager', $pager)
            ->render($this->config->templates[$template]);
    }

    
    public function store(string $group, int $page, ?int $perPage, int $total, int $segment = 0)
    {
        if ($segment !== 0) {
            $this->setSegment($segment, $group);
        }

        $this->ensureGroup($group, $perPage);

        if ($segment > 0 && $this->groups[$group]['currentPage'] > 0) {
            $page = $this->groups[$group]['currentPage'];
        }

        $perPage ??= $this->config->perPage;
        $pageCount = (int) ceil($total / $perPage);

        $this->groups[$group]['currentPage'] = $page > $pageCount ? $pageCount : $page;
        $this->groups[$group]['perPage']     = $perPage;
        $this->groups[$group]['total']       = $total;
        $this->groups[$group]['pageCount']   = $pageCount;

        return $this;
    }

    
    public function setSegment(int $number, string $group = 'default')
    {
        $this->segment[$group] = $number;

        
        $this->ensureGroup($group);
        $this->calculateCurrentPage($group);

        return $this;
    }

    
    public function setPath(string $path, string $group = 'default')
    {
        $this->ensureGroup($group);

        $this->groups[$group]['uri']->setPath($path);

        return $this;
    }

    
    public function getTotal(string $group = 'default'): int
    {
        $this->ensureGroup($group);

        return $this->groups[$group]['total'];
    }

    
    public function getPageCount(string $group = 'default'): int
    {
        $this->ensureGroup($group);

        return $this->groups[$group]['pageCount'];
    }

    
    public function getCurrentPage(string $group = 'default'): int
    {
        $this->ensureGroup($group);

        return $this->groups[$group]['currentPage'] ?: 1;
    }

    
    public function hasMore(string $group = 'default'): bool
    {
        $this->ensureGroup($group);

        return ($this->groups[$group]['currentPage'] * $this->groups[$group]['perPage']) < $this->groups[$group]['total'];
    }

    
    public function getLastPage(string $group = 'default')
    {
        $this->ensureGroup($group);

        if (! is_numeric($this->groups[$group]['total']) || ! is_numeric($this->groups[$group]['perPage'])) {
            return null;
        }

        return (int) ceil($this->groups[$group]['total'] / $this->groups[$group]['perPage']);
    }

    
    public function getFirstPage(string $group = 'default'): int
    {
        $this->ensureGroup($group);

        
        return 1;
    }

    
    public function getPageURI(?int $page = null, string $group = 'default', bool $returnObject = false)
    {
        $this->ensureGroup($group);

        
        $uri = $this->groups[$group]['uri'];

        $segment = $this->segment[$group] ?? 0;

        if ($segment) {
            $uri->setSegment($segment, $page);
        } else {
            $uri->addQuery($this->groups[$group]['pageSelector'], $page);
        }

        if ($this->only !== null) {
            $query = array_intersect_key(service('superglobals')->getGetArray(), array_flip($this->only));

            if (! $segment) {
                $query[$this->groups[$group]['pageSelector']] = $page;
            }

            $uri->setQueryArray($query);
        }

        return $returnObject
            ? $uri
            : URI::createURIString(
                $uri->getScheme(),
                $uri->getAuthority(),
                $uri->getPath(),
                $uri->getQuery(),
                $uri->getFragment(),
            );
    }

    
    public function getNextPageURI(string $group = 'default', bool $returnObject = false)
    {
        $this->ensureGroup($group);

        $last = $this->getLastPage($group);
        $curr = $this->getCurrentPage($group);
        $page = null;

        if (! empty($last) && $curr !== 0 && $last === $curr) {
            return null;
        }

        if ($last > $curr) {
            $page = $curr + 1;
        }

        return $this->getPageURI($page, $group, $returnObject);
    }

    
    public function getPreviousPageURI(string $group = 'default', bool $returnObject = false)
    {
        $this->ensureGroup($group);

        $first = $this->getFirstPage($group);
        $curr  = $this->getCurrentPage($group);
        $page  = null;

        if ($first !== 0 && $curr !== 0 && $first === $curr) {
            return null;
        }

        if ($first < $curr) {
            $page = $curr - 1;
        }

        return $this->getPageURI($page, $group, $returnObject);
    }

    
    public function getPerPage(string $group = 'default'): int
    {
        $this->ensureGroup($group);

        return (int) $this->groups[$group]['perPage'];
    }

    
    public function getDetails(string $group = 'default'): array
    {
        if (! array_key_exists($group, $this->groups)) {
            throw PagerException::forInvalidPaginationGroup($group);
        }

        $newGroup = $this->groups[$group];

        $newGroup['next']     = $this->getNextPageURI($group);
        $newGroup['previous'] = $this->getPreviousPageURI($group);
        $newGroup['segment']  = $this->segment[$group] ?? 0;

        return $newGroup;
    }

    
    public function only(array $queries): self
    {
        $this->only = $queries;

        return $this;
    }

    
    protected function ensureGroup(string $group, ?int $perPage = null)
    {
        if (array_key_exists($group, $this->groups)) {
            return;
        }

        $this->groups[$group] = [
            'currentUri'   => clone current_url(true),
            'uri'          => clone current_url(true),
            'hasMore'      => false,
            'total'        => null,
            'perPage'      => $perPage ?? $this->config->perPage,
            'pageCount'    => 1,
            'pageSelector' => $group === 'default' ? 'page' : 'page_' . $group,
        ];

        $this->calculateCurrentPage($group);

        $get = service('superglobals')->getGetArray();
        if ($get !== []) {
            $this->groups[$group]['uri'] = $this->groups[$group]['uri']->setQueryArray($get);
        }
    }

    
    protected function calculateCurrentPage(string $group)
    {
        if (array_key_exists($group, $this->segment)) {
            try {
                $this->groups[$group]['currentPage'] = (int) $this->groups[$group]['currentUri']
                    ->setSilent(false)->getSegment($this->segment[$group]);
            } catch (HTTPException) {
                $this->groups[$group]['currentPage'] = 1;
            }
        } else {
            $pageSelector = $this->groups[$group]['pageSelector'];

            $page = (int) (service('superglobals')->get($pageSelector, '1'));

            $this->groups[$group]['currentPage'] = $page < 1 ? 1 : $page;
        }
    }
}
