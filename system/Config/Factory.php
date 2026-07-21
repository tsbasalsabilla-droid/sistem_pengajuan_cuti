<?php

declare(strict_types=1);



namespace CodeIgniter\Config;


class Factory extends BaseConfig
{
    
    public static $default = [
        'component'  => null,
        'path'       => null,
        'instanceOf' => null,
        'getShared'  => true,
        'preferApp'  => true,
    ];

    
    public $models = [
        'preferApp' => true,
    ];
}
