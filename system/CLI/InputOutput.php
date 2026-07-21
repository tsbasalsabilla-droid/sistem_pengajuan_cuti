<?php

declare(strict_types=1);



namespace CodeIgniter\CLI;


class InputOutput
{
    
    private  bool $readlineSupport;

    public function __construct()
    {
        
        
        
        $this->readlineSupport = extension_loaded('readline');
    }

    
    public function input(?string $prefix = null): string
    {
        
        if ($this->readlineSupport && ENVIRONMENT !== 'testing') {
            return readline($prefix); 
        }

        echo $prefix;

        $input = fgets(fopen('php://stdin', 'rb'));

        if ($input === false) {
            $input = '';
        }

        return $input;
    }

    
    public function fwrite($handle, string $string): void
    {
        if (! is_cli()) {
            echo $string;

            return;
        }

        fwrite($handle, $string);
    }
}
