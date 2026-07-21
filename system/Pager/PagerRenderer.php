<?php

declare(strict_types=1);



namespace CodeIgniter\Pager;

use CodeIgniter\HTTP\URI;


class PagerRenderer
{
    
    protected $first = 1;

    
    protected $last;

    
    protected $current;

    
    protected $total;

    
    protected $pageCount;

    
    protected $uri;

    
    protected $segment;

    
    protected $pageSelector;

    
    protected ?int $perPage;

    
    protected ?int $perPageStart = null;

    
    protected ?int $perPageEnd = null;

    
    public function __construct(array $details)
    {
        $this->last = $details['pageCount'];

        $this->current      = $details['currentPage'];
        $this->total        = $details['total'];
        $this->uri          = $details['uri'];
        $this->pageCount    = $details['pageCount'];
        $this->segment      = $details['segment'] ?? 0;
        $this->pageSelector = $details['pageSelector'] ?? 'page';
        $this->perPage      = $details['perPage'] ?? null;
        $this->updatePerPages();
    }

    
    public function setSurroundCount(?int $count = null)
    {
        $this->updatePages($count);

        return $this;
    }

    
    public function hasPrevious(): bool
    {
        return $this->first > 1;
    }

    
    public function getPrevious()
    {
        if (! $this->hasPrevious()) {
            return null;
        }

        $uri = clone $this->uri;

        if ($this->segment === 0) {
            $uri->addQuery($this->pageSelector, $this->first - 1);
        } else {
            $uri->setSegment($this->segment, $this->first - 1);
        }

        return URI::createURIString(
            $uri->getScheme(),
            $uri->getAuthority(),
            $uri->getPath(),
            $uri->getQuery(),
            $uri->getFragment(),
        );
    }

    
    public function hasNext(): bool
    {
        return $this->pageCount > $this->last;
    }

    
    public function getNext()
    {
        if (! $this->hasNext()) {
            return null;
        }

        $uri = clone $this->uri;

        if ($this->segment === 0) {
            $uri->addQuery($this->pageSelector, $this->last + 1);
        } else {
            $uri->setSegment($this->segment, $this->last + 1);
        }

        return URI::createURIString(
            $uri->getScheme(),
            $uri->getAuthority(),
            $uri->getPath(),
            $uri->getQuery(),
            $uri->getFragment(),
        );
    }

    
    public function getFirst(): string
    {
        $uri = clone $this->uri;

        if ($this->segment === 0) {
            $uri->addQuery($this->pageSelector, 1);
        } else {
            $uri->setSegment($this->segment, 1);
        }

        return URI::createURIString(
            $uri->getScheme(),
            $uri->getAuthority(),
            $uri->getPath(),
            $uri->getQuery(),
            $uri->getFragment(),
        );
    }

    
    public function getLast(): string
    {
        $uri = clone $this->uri;

        if ($this->segment === 0) {
            $uri->addQuery($this->pageSelector, $this->pageCount);
        } else {
            $uri->setSegment($this->segment, $this->pageCount);
        }

        return URI::createURIString(
            $uri->getScheme(),
            $uri->getAuthority(),
            $uri->getPath(),
            $uri->getQuery(),
            $uri->getFragment(),
        );
    }

    
    public function getCurrent(): string
    {
        $uri = clone $this->uri;

        if ($this->segment === 0) {
            $uri->addQuery($this->pageSelector, $this->current);
        } else {
            $uri->setSegment($this->segment, $this->current);
        }

        return URI::createURIString(
            $uri->getScheme(),
            $uri->getAuthority(),
            $uri->getPath(),
            $uri->getQuery(),
            $uri->getFragment(),
        );
    }

    
    public function links(): array
    {
        $links = [];

        $uri = clone $this->uri;

        for ($i = $this->first; $i <= $this->last; $i++) {
            $uri     = $this->segment === 0 ? $uri->addQuery($this->pageSelector, $i) : $uri->setSegment($this->segment, $i);
            $links[] = [
                'uri' => URI::createURIString(
                    $uri->getScheme(),
                    $uri->getAuthority(),
                    $uri->getPath(),
                    $uri->getQuery(),
                    $uri->getFragment(),
                ),
                'title'  => $i,
                'active' => ($i === $this->current),
            ];
        }

        return $links;
    }

    
    protected function updatePages(?int $count = null)
    {
        if ($count === null) {
            return;
        }

        $this->first = $this->current - $count > 0 ? $this->current - $count : 1;
        $this->last  = $this->current + $count <= $this->pageCount ? $this->current + $count : (int) $this->pageCount;
    }

    
    protected function updatePerPages(): void
    {
        if ($this->total === null || $this->perPage === null) {
            return;
        }

        
        if ($this->last === $this->current) {
            $this->perPageStart = $this->perPage * ($this->current - 1) + 1;
            $this->perPageEnd   = $this->total;

            return;
        }

        $this->perPageStart = $this->current === 1 ? 1 : ($this->perPage * $this->current) - $this->perPage + 1;
        $this->perPageEnd   = $this->perPage * $this->current;
    }

    
    public function hasPreviousPage(): bool
    {
        return $this->current > 1;
    }

    
    public function getPreviousPage()
    {
        if (! $this->hasPreviousPage()) {
            return null;
        }

        $uri = clone $this->uri;

        if ($this->segment === 0) {
            $uri->addQuery($this->pageSelector, $this->current - 1);
        } else {
            $uri->setSegment($this->segment, $this->current - 1);
        }

        return URI::createURIString(
            $uri->getScheme(),
            $uri->getAuthority(),
            $uri->getPath(),
            $uri->getQuery(),
            $uri->getFragment(),
        );
    }

    
    public function hasNextPage(): bool
    {
        return $this->current < $this->last;
    }

    
    public function getNextPage()
    {
        if (! $this->hasNextPage()) {
            return null;
        }

        $uri = clone $this->uri;

        if ($this->segment === 0) {
            $uri->addQuery($this->pageSelector, $this->current + 1);
        } else {
            $uri->setSegment($this->segment, $this->current + 1);
        }

        return URI::createURIString(
            $uri->getScheme(),
            $uri->getAuthority(),
            $uri->getPath(),
            $uri->getQuery(),
            $uri->getFragment(),
        );
    }

    
    public function getFirstPageNumber(): int
    {
        return $this->first;
    }

    
    public function getCurrentPageNumber(): int
    {
        return $this->current;
    }

    
    public function getLastPageNumber(): int
    {
        return $this->last;
    }

    
    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    
    public function getPreviousPageNumber(): ?int
    {
        return ($this->current === 1) ? null : $this->current - 1;
    }

    
    public function getNextPageNumber(): ?int
    {
        return ($this->current === $this->pageCount) ? null : $this->current + 1;
    }

    
    public function getTotal(): ?int
    {
        return $this->total;
    }

    
    public function getPerPage(): ?int
    {
        return $this->perPage;
    }

    
    public function getPerPageStart(): ?int
    {
        return $this->perPageStart;
    }

    
    public function getPerPageEnd(): ?int
    {
        return $this->perPageEnd;
    }
}
