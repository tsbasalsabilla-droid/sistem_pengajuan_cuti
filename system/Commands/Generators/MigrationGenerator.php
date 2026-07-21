<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\GeneratorTrait;
use Config\Database;
use Config\Migrations;
use Config\Session as SessionConfig;


class MigrationGenerator extends BaseCommand
{
    use GeneratorTrait;

    
    protected $group = 'Generators';

    
    protected $name = 'make:migration';

    
    protected $description = 'Generates a new migration file.';

    
    protected $usage = 'make:migration <name> [options]';

    
    protected $arguments = [
        'name' => 'The migration class name.',
    ];

    
    protected $options = [
        '--session'   => 'Generates the migration file for database sessions.',
        '--table'     => 'Table name to use for database sessions. Default: "ci_sessions".',
        '--dbgroup'   => 'Database group to use for database sessions. Default: "default".',
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserMigration).',
    ];

    
    public function run(array $params)
    {
        $this->component = 'Migration';
        $this->directory = 'Database\Migrations';
        $this->template  = 'migration.tpl.php';

        if (array_key_exists('session', $params) || CLI::getOption('session')) {
            $table     = $params['table'] ?? CLI::getOption('table') ?? 'ci_sessions';
            $params[0] = "_create_{$table}_table";
        }

        $this->classNameLang = 'CLI.generator.className.migration';
        $this->generateClass($params);
    }

    
    protected function prepare(string $class): string
    {
        $data            = [];
        $data['session'] = false;

        if ($this->getOption('session')) {
            $table   = $this->getOption('table');
            $DBGroup = $this->getOption('dbgroup');

            $data['session']  = true;
            $data['table']    = is_string($table) ? $table : 'ci_sessions';
            $data['DBGroup']  = is_string($DBGroup) ? $DBGroup : 'default';
            $data['DBDriver'] = config(Database::class)->{$data['DBGroup']}['DBDriver'];

            $data['matchIP'] = config(SessionConfig::class)->matchIP;
        }

        return $this->parseTemplate($class, [], [], $data);
    }

    
    protected function basename(string $filename): string
    {
        return gmdate(config(Migrations::class)->timestampFormat) . basename($filename);
    }
}
