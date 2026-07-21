<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

interface ResponsableInterface
{
    public function getResponse(): ResponseInterface;
}
