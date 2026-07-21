<?php

declare(strict_types=1);



namespace CodeIgniter\Database\SQLSRV;

use CodeIgniter\Database\BaseUtils;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\Exceptions\DatabaseException;


class Utils extends BaseUtils
{
    
    protected $listDatabases = 'EXEC sp_helpdb'; 

    
    protected $optimizeTable = 'ALTER INDEX all ON %s REORGANIZE';

    public function __construct(ConnectionInterface $db)
    {
        parent::__construct($db);

        $this->optimizeTable = 'ALTER INDEX all ON  ' . $this->db->schema . '.%s REORGANIZE';
    }

    
    public function _backup(?array $prefs = null)
    {
        throw new DatabaseException('Unsupported feature of the database platform you are using.');
    }
}
