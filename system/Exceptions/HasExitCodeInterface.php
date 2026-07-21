<?php

declare(strict_types=1);



namespace CodeIgniter\Exceptions;


interface HasExitCodeInterface extends ExceptionInterface
{
    
    public function getExitCode(): int;
}
