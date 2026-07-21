<?php

declare(strict_types=1);



namespace CodeIgniter\Database\SQLite3;

use CodeIgniter\Database\BaseUtils;
use CodeIgniter\Database\Exceptions\DatabaseException;


class Utils extends BaseUtils
{
    
    protected $optimizeTable = 'REINDEX %s';

    
    public function _backup(?array $prefs = null)
    {
        throw new DatabaseException('Unsupported feature of the database platform you are using.');
    }
}
