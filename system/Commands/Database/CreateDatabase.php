<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Database;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Config\Factories;
use CodeIgniter\Database\SQLite3\Connection;
use Config\Database;
use Throwable;


class CreateDatabase extends BaseCommand
{
    
    protected $group = 'Database';

    
    protected $name = 'db:create';

    
    protected $description = 'Create a new database schema.';

    
    protected $usage = 'db:create <db_name> [options]';

    
    protected $arguments = [
        'db_name' => 'The database name to use',
    ];

    
    protected $options = [
        '--ext' => 'File extension of the database file for SQLite3. Can be `db` or `sqlite`. Defaults to `db`.',
    ];

    
    public function run(array $params)
    {
        $name = array_shift($params);

        if (empty($name)) {
            $name = CLI::prompt('Database name', null, 'required'); 
        }

        try {
            $config = config(Database::class);

            
            $group = ENVIRONMENT === 'testing' ? 'tests' : $config->defaultGroup;

            $config->{$group}['database'] = '';

            $db = Database::connect();

            
            if ($db instanceof Connection) {
                $ext = $params['ext'] ?? CLI::getOption('ext') ?? 'db';

                if (! in_array($ext, ['db', 'sqlite'], true)) {
                    $ext = CLI::prompt('Please choose a valid file extension', ['db', 'sqlite']); 
                }

                if ($name !== ':memory:') {
                    $name = str_replace(['.db', '.sqlite'], '', $name) . ".{$ext}";
                }

                $config->{$group}['DBDriver'] = 'SQLite3';
                $config->{$group}['database'] = $name;

                if ($name !== ':memory:') {
                    $dbName = str_contains($name, DIRECTORY_SEPARATOR) ? $name : WRITEPATH . $name;

                    if (is_file($dbName)) {
                        CLI::error("Database \"{$dbName}\" already exists.", 'light_gray', 'red');
                        CLI::newLine();

                        return;
                    }

                    unset($dbName);
                }

                
                $db = Database::connect(null, false);
                $db->connect();

                if (! is_file($db->getDatabase()) && $name !== ':memory:') {
                    
                    CLI::error('Database creation failed.', 'light_gray', 'red');
                    CLI::newLine();

                    return;
                    
                }
            } elseif (! Database::forge()->createDatabase($name)) {
                
                CLI::error('Database creation failed.', 'light_gray', 'red');
                CLI::newLine();

                return;
                
            }

            CLI::write("Database \"{$name}\" successfully created.", 'green');
            CLI::newLine();
        } catch (Throwable $e) {
            $this->showError($e);
        } finally {
            Factories::reset('config');
            Database::connect(null, false);
        }
    }
}
