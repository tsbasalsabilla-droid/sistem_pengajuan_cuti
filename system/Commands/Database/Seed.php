<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Database;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use Config\Database;
use Throwable;


class Seed extends BaseCommand
{
    
    protected $group = 'Database';

    
    protected $name = 'db:seed';

    
    protected $description = 'Runs the specified seeder to populate known data into the database.';

    
    protected $usage = 'db:seed <seeder_name>';

    
    protected $arguments = [
        'seeder_name' => 'The seeder name to run',
    ];

    
    public function run(array $params)
    {
        $seeder   = new Seeder(new Database());
        $seedName = array_shift($params);

        if (empty($seedName)) {
            $seedName = CLI::prompt(lang('Migrations.migSeeder'), null, 'required'); 
        }

        try {
            $seeder->call($seedName);
        } catch (Throwable $e) {
            $this->showError($e);
        }
    }
}
