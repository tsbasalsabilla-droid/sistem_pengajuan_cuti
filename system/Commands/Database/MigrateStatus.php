<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Database;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;


class MigrateStatus extends BaseCommand
{
    
    protected $group = 'Database';

    
    protected $name = 'migrate:status';

    
    protected $description = 'Displays a list of all migrations and whether they\'ve been run or not.';

    
    protected $usage = 'migrate:status [options]';

    
    protected $options = [
        '-g' => 'Set database group',
    ];

    
    protected $ignoredNamespaces = [
        'CodeIgniter',
        'Config',
        'Kint',
        'Laminas\ZendFrameworkBridge',
        'Laminas\Escaper',
        'Psr\Log',
    ];

    
    public function run(array $params)
    {
        $runner     = service('migrations');
        $paramGroup = $params['g'] ?? CLI::getOption('g');

        
        $namespaces = service('autoloader')->getNamespace();

        
        $status = [];

        foreach (array_keys($namespaces) as $namespace) {
            if (ENVIRONMENT !== 'testing') {
                
                $this->ignoredNamespaces[] = 'Tests\Support'; 
            }

            if (in_array($namespace, $this->ignoredNamespaces, true)) {
                continue;
            }

            if (APP_NAMESPACE !== 'App' && $namespace === 'App') {
                continue; 
            }

            $migrations = $runner->findNamespaceMigrations($namespace);

            if (empty($migrations)) {
                continue;
            }

            $runner->setNamespace($namespace);
            $history = $runner->getHistory((string) $paramGroup);
            ksort($migrations);

            foreach ($migrations as $uid => $migration) {
                $migrations[$uid]->name = mb_substr($migration->name, (int) mb_strpos($migration->name, $uid . '_'));

                $date  = '---';
                $group = '---';
                $batch = '---';

                foreach ($history as $row) {
                    
                    if ($runner->getObjectUid($row) !== $migration->uid) {
                        continue;
                    }

                    $date  = date('Y-m-d H:i:s', (int) $row->time);
                    $group = $row->group;
                    $batch = $row->batch;
                    
                }

                $status[] = [
                    $namespace,
                    $migration->version,
                    $migration->name,
                    $group,
                    $date,
                    $batch,
                ];
            }
        }

        if ($status === []) {
            
            CLI::error(lang('Migrations.noneFound'), 'light_gray', 'red');
            CLI::newLine();

            return;
            
        }

        $headers = [
            CLI::color(lang('Migrations.namespace'), 'yellow'),
            CLI::color(lang('Migrations.version'), 'yellow'),
            CLI::color(lang('Migrations.filename'), 'yellow'),
            CLI::color(lang('Migrations.group'), 'yellow'),
            CLI::color(str_replace(': ', '', lang('Migrations.on')), 'yellow'),
            CLI::color(lang('Migrations.batch'), 'yellow'),
        ];

        CLI::table($status, $headers);
    }
}
