<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Housekeeping;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;


class ClearDebugbar extends BaseCommand
{
    
    protected $group = 'Housekeeping';

    
    protected $name = 'debugbar:clear';

    
    protected $usage = 'debugbar:clear';

    
    protected $description = 'Clears all debugbar JSON files.';

    
    public function run(array $params)
    {
        helper('filesystem');

        if (! delete_files(WRITEPATH . 'debugbar', false, true)) {
            
            CLI::error('Error deleting the debugbar JSON files.');
            CLI::newLine();

            return;
            
        }

        CLI::write('Debugbar cleared.', 'green');
        CLI::newLine();
    }
}
