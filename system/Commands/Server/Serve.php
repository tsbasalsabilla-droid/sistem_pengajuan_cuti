<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Server;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;


class Serve extends BaseCommand
{
    
    protected $group = 'CodeIgniter';

    
    protected $name = 'serve';

    
    protected $description = 'Launches the CodeIgniter PHP-Development Server.';

    
    protected $usage = 'serve';

    
    protected $arguments = [];

    
    protected $portOffset = 0;

    
    protected $tries = 10;

    
    protected $options = [
        '--php'  => 'The PHP Binary [default: "PHP_BINARY"]',
        '--host' => 'The HTTP Host [default: "localhost"]',
        '--port' => 'The HTTP Host Port [default: "8080"]',
    ];

    
    public function run(array $params)
    {
        
        $php  = escapeshellarg(CLI::getOption('php') ?? PHP_BINARY);
        $host = CLI::getOption('host') ?? 'localhost';
        $port = (int) (CLI::getOption('port') ?? 8080) + $this->portOffset;

        
        CLI::write('CodeIgniter development server started on http://' . $host . ':' . $port, 'green');
        CLI::write('Press Control-C to stop.');

        
        $docroot = escapeshellarg(FCPATH);

        
        $rewrite = escapeshellarg(SYSTEMPATH . 'rewrite.php');

        
        
        
        passthru($php . ' -S ' . $host . ':' . $port . ' -t ' . $docroot . ' ' . $rewrite, $status);

        if ($status !== EXIT_SUCCESS && $this->portOffset < $this->tries) {
            $this->portOffset++;

            $this->run($params);
        }
    }
}
