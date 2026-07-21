<?php

declare(strict_types=1);



namespace CodeIgniter\Pager;

use CodeIgniter\HTTP\URI;


interface PagerInterface
{
    
    public function links(string $group = 'default', string $template = 'default'): string;

    
    public function simpleLinks(string $group = 'default', string $template = 'default'): string;

    
    public function makeLinks(int $page, int $perPage, int $total, string $template = 'default'): string;

    
    public function store(string $group, int $page, int $perPage, int $total);

    
    public function setPath(string $path, string $group = 'default');

    
    public function getPageCount(string $group = 'default'): int;

    
    public function getCurrentPage(string $group = 'default'): int;

    
    public function getPageURI(?int $page = null, string $group = 'default', bool $returnObject = false);

    
    public function hasMore(string $group = 'default'): bool;

    
    public function getFirstPage(string $group = 'default');

    
    public function getLastPage(string $group = 'default');

    
    public function getNextPageURI(string $group = 'default');

    
    public function getPreviousPageURI(string $group = 'default');

    
    public function getPerPage(string $group = 'default'): int;

    
    public function getDetails(string $group = 'default'): array;
}
