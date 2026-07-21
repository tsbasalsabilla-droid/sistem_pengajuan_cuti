<?php

declare(strict_types=1);



namespace CodeIgniter\Database\MySQLi;

use CodeIgniter\Database\BaseUtils;
use CodeIgniter\Database\Exceptions\DatabaseException;


class Utils extends BaseUtils
{
    
    protected $listDatabases = 'SHOW DATABASES';

    
    protected $optimizeTable = 'OPTIMIZE TABLE %s';

    
    public function _backup(?array $prefs = null)
    {
        throw new DatabaseException('Unsupported feature of the database platform you are using.');
    }
}
