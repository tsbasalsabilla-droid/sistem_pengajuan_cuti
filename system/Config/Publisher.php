<?php

declare(strict_types=1);



namespace CodeIgniter\Config;


class Publisher extends BaseConfig
{
    
    public $restrictions = [
        ROOTPATH => '*',
        FCPATH   => '#\.(?css|js|map|htm?|xml|json|webmanifest|tff|eot|woff?|gif|jpe?g|tiff?|png|webp|bmp|ico|svg)$#i',
    ];

    
    final protected function registerProperties(): void
    {
    }
}
