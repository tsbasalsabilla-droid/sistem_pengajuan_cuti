<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Database;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\SignalTrait;
use CodeIgniter\Database\MigrationRunner;
use Throwable;


class MigrateRollback extends BaseCommand
{
    use SignalTrait;

    
    protected $group = 'Database';

    
    protected $name = 'migrate:rollback';

    
    protected $description = 'Runs the "down" method for all migrations in the last batch.';

    
    protected $usage = 'migrate:rollback [options]';

    
    protected $options = [
        '-b' => 'Specify a batch to roll back to; e.g. "3" to return to batch #3',
        '-f' => 'Force command - this option allows you to bypass the confirmation question when running this command in a production environment',
    ];

    
    public function run(array $params)
    {
        if (ENVIRONMENT === 'production') {
            
            $force = array_key_exists('f', $params) || CLI::getOption('f');

            if (! $force && CLI::prompt(lang('Migrations.rollBackConfirm'), ['y', 'n']) === 'n') {
                return null;
            }
            
        }

        
        $runner = service('migrations');

        try {
            $batch = $params['b'] ?? CLI::getOption('b') ?? $runner->getLastBatch() - 1;

            if (is_string($batch)) {
                if (! ctype_digit($batch)) {
                    CLI::error('Invalid batch number: ' . $batch, 'light_gray', 'red');
                    CLI::newLine();

                    return EXIT_ERROR;
                }

                $batch = (int) $batch;
            }

            CLI::write(lang('Migrations.rollingBack') . ' ' . $batch, 'yellow');

            $this->withSignalsBlocked(static function () use ($runner, $batch): void {
                if (! $runner->regress($batch)) {
                    CLI::error(lang('Migrations.generalFault'), 'light_gray', 'red'); 
                }
            });

            $messages = $runner->getCliMessages();

            foreach ($messages as $message) {
                CLI::write($message);
            }

            CLI::write('Done rolling back migrations.', 'green');

            
        } catch (Throwable $e) {
            $this->showError($e);
            
        }

        return null;
    }
}
