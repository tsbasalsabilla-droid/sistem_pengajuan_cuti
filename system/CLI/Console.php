<?php

declare(strict_types=1);



namespace CodeIgniter\CLI;

use CodeIgniter\CodeIgniter;
use Config\App;
use Config\Services;
use Exception;


class Console
{
    
    public function run()
    {
        
        $appConfig = config(App::class);
        Services::createRequest($appConfig, true);
        
        service('routes')->loadRoutes();

        $params  = array_merge(CLI::getSegments(), CLI::getOptions());
        $params  = $this->parseParamsForHelpOption($params);
        $command = array_shift($params) ?? 'list';

        return service('commands')->run($command, $params);
    }

    
    public function showHeader(bool $suppress = false)
    {
        if ($suppress) {
            return;
        }

        CLI::write(sprintf(
            'CodeIgniter v%s Command Line Tool - Server Time: %s',
            CodeIgniter::CI_VERSION,
            date('Y-m-d H:i:s \\U\\T\\CP'),
        ), 'green');
        CLI::newLine();
    }

    
    private function parseParamsForHelpOption(array $params): array
    {
        if (array_key_exists('help', $params)) {
            unset($params['help']);

            $params = $params === [] ? ['list'] : $params;
            array_unshift($params, 'help');
        }

        return $params;
    }
}
