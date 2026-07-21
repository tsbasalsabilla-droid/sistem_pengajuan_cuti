<?php

declare(strict_types=1);



namespace CodeIgniter\View;


interface ViewDecoratorInterface
{
    
    public static function decorate(string $html): string;
}
