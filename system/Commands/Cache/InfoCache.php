<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Cache;

use CodeIgniter\Cache\CacheFactory;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;
use Config\Cache;


class InfoCache extends BaseCommand
{
    
    protected $group = 'Cache';

    
    protected $name = 'cache:info';

    
    protected $description = 'Shows file cache information in the current system.';

    
    protected $usage = 'cache:info';

    
    public function run(array $params)
    {
        $config = config(Cache::class);
        helper('number');

        if ($config->handler !== 'file') {
            CLI::error('This command only supports the file cache handler.');

            return;
        }

        $cache  = CacheFactory::getHandler($config);
        $caches = $cache->getCacheInfo();
        $tbody  = [];

        foreach ($caches as $key => $field) {
            $tbody[] = [
                $key,
                clean_path($field['server_path']),
                number_to_size($field['size']),
                Time::createFromTimestamp($field['date']),
            ];
        }

        $thead = [
            CLI::color('Name', 'green'),
            CLI::color('Server Path', 'green'),
            CLI::color('Size', 'green'),
            CLI::color('Date', 'green'),
        ];

        CLI::table($tbody, $thead);
    }
}
