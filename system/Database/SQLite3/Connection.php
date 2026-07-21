<?php

declare(strict_types=1);



namespace CodeIgniter\Database\SQLite3;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\TableName;
use CodeIgniter\Exceptions\InvalidArgumentException;
use Exception;
use SQLite3;
use SQLite3Result;
use stdClass;


class Connection extends BaseConnection
{
    
    public $DBDriver = 'SQLite3';

    
    public $escapeChar = '`';

    
    protected $foreignKeys = false;

    
    protected ?int $busyTimeout = null;

    
    protected ?int $synchronous = null;

    
    public function initialize()
    {
        parent::initialize();

        if ($this->foreignKeys) {
            $this->enableForeignKeyChecks();
        }

        if (is_int($this->busyTimeout)) {
            $this->connID->busyTimeout($this->busyTimeout);
        }

        if (is_int($this->synchronous)) {
            if (! in_array($this->synchronous, [0, 1, 2, 3], true)) {
                throw new InvalidArgumentException('Invalid synchronous value.');
            }
            $this->connID->exec('PRAGMA synchronous = ' . $this->synchronous);
        }
    }

    
    public function connect(bool $persistent = false)
    {
        if ($persistent && $this->DBDebug) {
            throw new DatabaseException('SQLite3 doesn\'t support persistent connections.');
        }

        try {
            if ($this->database !== ':memory:' && ! str_contains($this->database, DIRECTORY_SEPARATOR)) {
                $this->database = WRITEPATH . $this->database;
            }

            $sqlite = ($this->password === null || $this->password === '')
                ? new SQLite3($this->database)
                : new SQLite3($this->database, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, $this->password);

            $sqlite->enableExceptions(true);

            return $sqlite;
        } catch (Exception $e) {
            throw new DatabaseException('SQLite3 error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    
    protected function _close()
    {
        $this->connID->close();
    }

    
    public function setDatabase(string $databaseName): bool
    {
        return false;
    }

    
    public function getVersion(): string
    {
        if (isset($this->dataCache['version'])) {
            return $this->dataCache['version'];
        }

        $version = SQLite3::version();

        return $this->dataCache['version'] = $version['versionString'];
    }

    
    protected function execute(string $sql)
    {
        try {
            return $this->isWriteType($sql)
                ? $this->connID->exec($sql)
                : $this->connID->query($sql);
        } catch (Exception $e) {
            log_message('error', "{message}\nin {exFile} on line {exLine}.\n{trace}", [
                'message' => $e->getMessage(),
                'exFile'  => clean_path($e->getFile()),
                'exLine'  => $e->getLine(),
                'trace'   => render_backtrace($e->getTrace()),
            ]);

            if ($this->DBDebug) {
                throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return false;
    }

    
    public function affectedRows(): int
    {
        return $this->connID->changes();
    }

    
    protected function _escapeString(string $str): string
    {
        if (! $this->connID instanceof SQLite3) {
            $this->initialize();
        }

        return $this->connID->escapeString($str);
    }

    
    protected function _listTables(bool $prefixLimit = false, ?string $tableName = null): string
    {
        if ((string) $tableName !== '') {
            return 'SELECT "NAME" FROM "SQLITE_MASTER" WHERE "TYPE" = \'table\''
                   . ' AND "NAME" NOT LIKE \'sqlite!_%\' ESCAPE \'!\''
                   . ' AND "NAME" LIKE ' . $this->escape($tableName);
        }

        return 'SELECT "NAME" FROM "SQLITE_MASTER" WHERE "TYPE" = \'table\''
               . ' AND "NAME" NOT LIKE \'sqlite!_%\' ESCAPE \'!\''
               . (($prefixLimit && $this->DBPrefix !== '')
                    ? ' AND "NAME" LIKE \'' . $this->escapeLikeString($this->DBPrefix) . '%\' ' . sprintf($this->likeEscapeStr, $this->likeEscapeChar)
                    : '');
    }

    
    protected function _listColumns($table = ''): string
    {
        if ($table instanceof TableName) {
            $tableName = $this->escapeIdentifier($table);
        } else {
            $tableName = $this->protectIdentifiers($table, true, null, false);
        }

        return 'PRAGMA TABLE_INFO(' . $tableName . ')';
    }

    
    public function getFieldNames($tableName)
    {
        $table = ($tableName instanceof TableName) ? $tableName->getTableName() : $tableName;

        
        if (isset($this->dataCache['field_names'][$table])) {
            return $this->dataCache['field_names'][$table];
        }

        if (! $this->connID instanceof SQLite3) {
            $this->initialize();
        }

        $sql = $this->_listColumns($tableName);

        $query                                  = $this->query($sql);
        $this->dataCache['field_names'][$table] = [];

        foreach ($query->getResultArray() as $row) {
            
            if (! isset($key)) {
                if (isset($row['column_name'])) {
                    $key = 'column_name';
                } elseif (isset($row['COLUMN_NAME'])) {
                    $key = 'COLUMN_NAME';
                } elseif (isset($row['name'])) {
                    $key = 'name';
                } else {
                    
                    $key = key($row);
                }
            }

            $this->dataCache['field_names'][$table][] = $row[$key];
        }

        return $this->dataCache['field_names'][$table];
    }

    
    protected function _fieldData(string $table): array
    {
        if (false === $query = $this->query('PRAGMA TABLE_INFO(' . $this->protectIdentifiers($table, true, null, false) . ')')) {
            throw new DatabaseException(lang('Database.failGetFieldData'));
        }

        $query = $query->getResultObject();

        if (empty($query)) {
            return [];
        }

        $retVal = [];

        for ($i = 0, $c = count($query); $i < $c; $i++) {
            $retVal[$i] = new stdClass();

            $retVal[$i]->name       = $query[$i]->name;
            $retVal[$i]->type       = $query[$i]->type;
            $retVal[$i]->max_length = null;
            $retVal[$i]->nullable   = isset($query[$i]->notnull) && ! (bool) $query[$i]->notnull;
            $retVal[$i]->default    = $query[$i]->dflt_value;
            
            
            
            $retVal[$i]->primary_key = ($query[$i]->pk === 0) ? 0 : 1;
        }

        return $retVal;
    }

    
    protected function _indexData(string $table): array
    {
        $sql = "SELECT 'PRIMARY' as indexname, l.name as fieldname, 'PRIMARY' as indextype
                FROM pragma_table_info(" . $this->escape(strtolower($table)) . ") as l
                WHERE l.pk <> 0
                UNION ALL
                SELECT sqlite_master.name as indexname, ii.name as fieldname,
                CASE
                WHEN ti.pk <> 0 AND sqlite_master.name LIKE 'sqlite_autoindex_%' THEN 'PRIMARY'
                WHEN sqlite_master.name LIKE 'sqlite_autoindex_%' THEN 'UNIQUE'
                WHEN sqlite_master.sql LIKE '% UNIQUE %' THEN 'UNIQUE'
                ELSE 'INDEX'
                END as indextype
                FROM sqlite_master
                INNER JOIN pragma_index_xinfo(sqlite_master.name) ii ON ii.name IS NOT NULL
                LEFT JOIN pragma_table_info(" . $this->escape(strtolower($table)) . ") ti ON ti.name = ii.name
                WHERE sqlite_master.type='index' AND sqlite_master.tbl_name = " . $this->escape(strtolower($table)) . ' COLLATE NOCASE';

        if (($query = $this->query($sql)) === false) {
            throw new DatabaseException(lang('Database.failGetIndexData'));
        }
        $query = $query->getResultObject();

        $tempVal = [];

        foreach ($query as $row) {
            if ($row->indextype === 'PRIMARY') {
                $tempVal['PRIMARY']['indextype']               = $row->indextype;
                $tempVal['PRIMARY']['indexname']               = $row->indexname;
                $tempVal['PRIMARY']['fields'][$row->fieldname] = $row->fieldname;
            } else {
                $tempVal[$row->indexname]['indextype']               = $row->indextype;
                $tempVal[$row->indexname]['indexname']               = $row->indexname;
                $tempVal[$row->indexname]['fields'][$row->fieldname] = $row->fieldname;
            }
        }

        $retVal = [];

        foreach ($tempVal as $val) {
            $obj                = new stdClass();
            $obj->name          = $val['indexname'];
            $obj->fields        = array_values($val['fields']);
            $obj->type          = $val['indextype'];
            $retVal[$obj->name] = $obj;
        }

        return $retVal;
    }

    
    protected function _foreignKeyData(string $table): array
    {
        if (! $this->supportsForeignKeys()) {
            return [];
        }

        $query   = $this->query("PRAGMA foreign_key_list({$table})")->getResult();
        $indexes = [];

        foreach ($query as $row) {
            $indexes[$row->id]['constraint_name']       = null;
            $indexes[$row->id]['table_name']            = $table;
            $indexes[$row->id]['foreign_table_name']    = $row->table;
            $indexes[$row->id]['column_name'][]         = $row->from;
            $indexes[$row->id]['foreign_column_name'][] = $row->to;
            $indexes[$row->id]['on_delete']             = $row->on_delete;
            $indexes[$row->id]['on_update']             = $row->on_update;
            $indexes[$row->id]['match']                 = $row->match;
        }

        return $this->foreignKeyDataToObjects($indexes);
    }

    
    protected function _disableForeignKeyChecks()
    {
        return 'PRAGMA foreign_keys = OFF';
    }

    
    protected function _enableForeignKeyChecks()
    {
        return 'PRAGMA foreign_keys = ON';
    }

    
    public function error(): array
    {
        return [
            'code'    => $this->connID->lastErrorCode(),
            'message' => $this->connID->lastErrorMsg(),
        ];
    }

    
    public function insertID(): int
    {
        return $this->connID->lastInsertRowID();
    }

    
    protected function _transBegin(): bool
    {
        return $this->connID->exec('BEGIN TRANSACTION');
    }

    
    protected function _transCommit(): bool
    {
        return $this->connID->exec('END TRANSACTION');
    }

    
    protected function _transRollback(): bool
    {
        return $this->connID->exec('ROLLBACK');
    }

    
    public function supportsForeignKeys(): bool
    {
        $result = $this->simpleQuery('PRAGMA foreign_keys');

        return (bool) $result;
    }
}
