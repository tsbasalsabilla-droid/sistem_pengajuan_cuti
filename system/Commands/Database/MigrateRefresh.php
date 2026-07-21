<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Database;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\SignalTrait;


class MigrateRefresh extends BaseCommand
{
    use SignalTrait;

    
    protected $group = 'Database';

    
    protected $name = 'migrate:refresh';

    
    protected $description = 'Does a rollback followed by a latest to refresh the current state of the database.';

    
    protected $usage = 'migrate:refresh [options]';

    
    protected $options = [
        '-n'    => 'Set migration namespace',
        '-g'    => 'Set database group',
        '--all' => 'Set latest for all namespace, will ignore (-n) option',
        '-f'    => 'Force command - this option allows you to bypass the confirmation question when running this command in a production environment',
    ];

    
    public function run(array $params)
    {
        $params['b'] = 0;

        if (ENVIRONMENT === 'production') {
            
            $force = array_key_exists('f', $params) || CLI::getOption('f');

            if (! $force && CLI::prompt(lang('Migrations.refreshConfirm'), ['y', 'n']) === 'n') {
                return;
            }

            $params['f'] = null;
            
        }

        $this->withSignalsBlocked(function () use ($params): void {
            $this->call('migrate:rollback', $params);
            $this->call('migrate', $params);
        });
    }
}
