<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Housekeeping;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;


class ClearLogs extends BaseCommand
{
    
    protected $group = 'Housekeeping';

    
    protected $name = 'logs:clear';

    
    protected $description = 'Clears all log files.';

    
    protected $usage = 'logs:clear [option';

    
    protected $options = [
        '--force' => 'Force delete of all logs files without prompting.',
    ];

    
    public function run(array $params)
    {
        $force = array_key_exists('force', $params) || CLI::getOption('force');

        if (! $force && CLI::prompt('Are you sure you want to delete the logs?', ['n', 'y']) === 'n') {
            
            CLI::error('Deleting logs aborted.', 'light_gray', 'red');
            CLI::error('If you want, use the "-force" option to force delete all log files.', 'light_gray', 'red');
            CLI::newLine();

            return;
            
        }

        helper('filesystem');

        if (! delete_files(WRITEPATH . 'logs', false, true)) {
            
            CLI::error('Error in deleting the logs files.', 'light_gray', 'red');
            CLI::newLine();

            return;
            
        }

        CLI::write('Logs cleared.', 'green');
        CLI::newLine();
    }
}
