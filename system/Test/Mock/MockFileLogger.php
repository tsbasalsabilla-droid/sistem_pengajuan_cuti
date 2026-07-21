<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\Log\Handlers\FileHandler;


class MockFileLogger extends FileHandler
{
    
    public $destination;

    
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->destination = $this->path . 'log-' . date('Y-m-d') . '.' . $this->fileExtension;
    }
}
