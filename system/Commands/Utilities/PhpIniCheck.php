<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Security\CheckPhpIni;


final class PhpIniCheck extends BaseCommand
{
    
    protected $group = 'CodeIgniter';

    
    protected $name = 'phpini:check';

    
    protected $description = 'Check your php.ini values in production environment.';

    
    protected $usage = 'phpini:check';

    
    protected $arguments = [
        'opcache' => 'Check detail opcache values in production environment.',
    ];

    
    protected $options = [];

    
    public function run(array $params)
    {
        if (isset($params[0]) && ! in_array($params[0], array_keys($this->arguments), true)) {
            CLI::error('You must specify a correct argument.');
            CLI::write('    Usage: ' . $this->usage);
            CLI::write('  Example: phpini:check opcache');
            CLI::write('Arguments:');

            $length = max(array_map(strlen(...), array_keys($this->arguments)));

            foreach ($this->arguments as $argument => $description) {
                CLI::write(CLI::color($this->setPad($argument, $length, 2, 2), 'green') . $description);
            }

            return EXIT_ERROR;
        }

        $argument = $params[0] ?? null;

        CheckPhpIni::run(argument: $argument);

        return EXIT_SUCCESS;
    }
}
