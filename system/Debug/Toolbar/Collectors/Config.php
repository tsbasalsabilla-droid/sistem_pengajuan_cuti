<?php

declare(strict_types=1);



namespace CodeIgniter\Debug\Toolbar\Collectors;

use CodeIgniter\CodeIgniter;
use Config\App;


class Config
{
    
    public static function display(): array
    {
        $config = config(App::class);

        return [
            'ciVersion'   => CodeIgniter::CI_VERSION,
            'phpVersion'  => PHP_VERSION,
            'phpSAPI'     => PHP_SAPI,
            'environment' => ENVIRONMENT,
            'baseURL'     => $config->baseURL,
            'timezone'    => app_timezone(),
            'locale'      => service('request')->getLocale(),
            'cspEnabled'  => $config->CSPEnabled,
        ];
    }
}
