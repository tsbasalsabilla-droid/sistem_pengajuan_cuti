<?php

declare(strict_types=1);



namespace CodeIgniter\Database\Postgre;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\TableName;
use ErrorException;
use PgSql\Connection as PgSqlConnection;
use PgSql\Result as PgSqlResult;
use stdClass;
use Stringable;


class Connection extends BaseConnection
{
    
    public $DBDriver = 'Postgre';

    
    public $schema = 'public';

    
    public $escapeChar = '"';

    protected $connect_timeout;
    protected $options;
    protected $sslmode;
    protected $service;

    
    public function connect(bool $persistent = false)
    {
        if (empty($this->DSN)) {
            $this->buildDSN();
        }

        
        
        
        
        if (mb_strpos($this->DSN, 'pgsql:') === 0) {
            $this->convertDSN();
        }

        $this->connID = $persistent ? pg_pconnect($this->DSN) : pg_connect($this->DSN);

        if ($this->connID !== false) {
            if (
                $persistent
                && pg_connection_status($this->connID) === PGSQL_CONNECTION_BAD
                && pg_ping($this->connID) === false
            ) {
                $error = pg_last_error($this->connID);

                throw new DatabaseException($error);
            }

            if (! empty($this->schema)) {
                $this->simpleQuery("SET search_path TO {$this->schema},public");
            }

            if ($this->setClientEncoding($this->charset) === false) {
                $error = pg_last_error($this->connID);

                throw new DatabaseException($error);
            }
        }

        return $this->connID;
    }

    
    private function convertDSN()
    {
        
        $this->DSN = mb_substr($this->DSN, 6);

        
        
        
        $allowedParams = ['host', 'port', 'dbname', 'user', 'password', 'connect_timeout', 'options', 'sslmode', 'service'];

        $parameters = explode(';', $this->DSN);

        $output            = '';
        $previousParameter = '';

        foreach ($parameters as $parameter) {
            [$key, $value] = explode('=', $parameter, 2);
            if (in_array($key, $allowedParams, true)) {
                if ($previousParameter !== '') {
                    if (array_search($key, $allowedParams, true) < array_search($previousParameter, $allowedParams, true)) {
                        $output .= ';';
                    } else {
                        $output .= ' ';
                    }
                }
                $output .= $parameter;
                $previousParameter = $key;
            } else {
                $output .= ';' . $parameter;
            }
        }

        $this->DSN = $output;
    }

    
    protected function _close()
    {
        pg_close($this->connID);
    }

    
    protected function _ping(): bool
    {
        return pg_ping($this->connID);
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

        if (! $this->connID) {
            $this->initialize();
        }

        $pgVersion                  = pg_version($this->connID);
        $this->dataCache['version'] = isset($pgVersion['server']) ?
            (preg_match('/^(\d+\.\d+)/', $pgVersion['server'], $matches) ? $matches[1] : '') :
            '';

        return $this->dataCache['version'];
    }

    
    protected function execute(string $sql)
    {
        try {
            return pg_query($this->connID, $sql);
        } catch (ErrorException $e) {
            $trace = array_slice($e->getTrace(), 2); 

            log_message('error', "{message}\nin {exFile} on line {exLine}.\n{trace}", [
                'message' => $e->getMessage(),
                'exFile'  => clean_path($e->getFile()),
                'exLine'  => $e->getLine(),
                'trace'   => render_backtrace($trace),
            ]);

            if ($this->DBDebug) {
                throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return false;
    }

    
    protected function getDriverFunctionPrefix(): string
    {
        return 'pg_';
    }

    
    public function affectedRows(): int
    {
        if ($this->resultID === false) {
            return 0;
        }

        return pg_affected_rows($this->resultID);
    }

    
    public function escape($str)
    {
        if (! $this->connID) {
            $this->initialize();
        }

        if ($str instanceof Stringable) {
            if ($str instanceof RawSql) {
                return $str->__toString();
            }

            $str = (string) $str;
        }

        if (is_string($str)) {
            return pg_escape_literal($this->connID, $str);
        }

        if (is_bool($str)) {
            return $str ? 'TRUE' : 'FALSE';
        }

        return parent::escape($str);
    }

    
    protected function _escapeString(string $str): string
    {
        if (! $this->connID) {
            $this->initialize();
        }

        return pg_escape_string($this->connID, $str);
    }

    
    protected function _listTables(bool $prefixLimit = false, ?string $tableName = null): string
    {
        $sql = 'SELECT "table_name" FROM "information_schema"."tables" WHERE "table_schema" = \'' . $this->schema . "'";

        if ($tableName !== null) {
            return $sql . ' AND "table_name" LIKE ' . $this->escape($tableName);
        }

        if ($prefixLimit && $this->DBPrefix !== '') {
            return $sql . ' AND "table_name" LIKE \''
                . $this->escapeLikeString($this->DBPrefix) . "%' "
                . sprintf($this->likeEscapeStr, $this->likeEscapeChar);
        }

        return $sql;
    }

    
    protected function _listColumns($table = ''): string
    {
        if ($table instanceof TableName) {
            $tableName = $this->escape($table->getActualTableName());
        } else {
            $tableName = $this->escape($this->DBPrefix . strtolower($table));
        }

        return 'SELECT "column_name"
			FROM "information_schema"."columns"
			WHERE LOWER("table_name") = ' . $tableName
                . ' ORDER BY "ordinal_position"';
    }

    
    protected function _fieldData(string $table): array
    {
        $sql = 'SELECT "column_name", "data_type", "character_maximum_length", "numeric_precision", "column_default",  "is_nullable"
            FROM "information_schema"."columns"
            WHERE LOWER("table_name") = '
                . $this->escape(strtolower($table))
                . ' ORDER BY "ordinal_position"';

        if (($query = $this->query($sql)) === false) {
            throw new DatabaseException(lang('Database.failGetFieldData'));
        }
        $query = $query->getResultObject();

        $retVal = [];

        for ($i = 0, $c = count($query); $i < $c; $i++) {
            $retVal[$i] = new stdClass();

            $retVal[$i]->name       = $query[$i]->column_name;
            $retVal[$i]->type       = $query[$i]->data_type;
            $retVal[$i]->max_length = $query[$i]->character_maximum_length > 0 ? $query[$i]->character_maximum_length : $query[$i]->numeric_precision;
            $retVal[$i]->nullable   = $query[$i]->is_nullable === 'YES';
            $retVal[$i]->default    = $query[$i]->column_default;
        }

        return $retVal;
    }

    
    protected function _indexData(string $table): array
    {
        $sql = 'SELECT "indexname", "indexdef"
			FROM "pg_indexes"
			WHERE LOWER("tablename") = ' . $this->escape(strtolower($table)) . '
			AND "schemaname" = ' . $this->escape('public');

        if (($query = $this->query($sql)) === false) {
            throw new DatabaseException(lang('Database.failGetIndexData'));
        }
        $query = $query->getResultObject();

        $retVal = [];

        foreach ($query as $row) {
            $obj         = new stdClass();
            $obj->name   = $row->indexname;
            $_fields     = explode(',', preg_replace('/^.*\((.+?)\)$/', '$1', trim($row->indexdef)));
            $obj->fields = array_map(trim(...), $_fields);

            if (str_starts_with($row->indexdef, 'CREATE UNIQUE INDEX pk')) {
                $obj->type = 'PRIMARY';
            } else {
                $obj->type = (str_starts_with($row->indexdef, 'CREATE UNIQUE')) ? 'UNIQUE' : 'INDEX';
            }

            $retVal[$obj->name] = $obj;
        }

        return $retVal;
    }

    
    protected function _foreignKeyData(string $table): array
    {
        $sql = 'SELECT c.constraint_name,
                x.table_name,
                x.column_name,
                y.table_name as foreign_table_name,
                y.column_name as foreign_column_name,
                c.delete_rule,
                c.update_rule,
                c.match_option
                FROM information_schema.referential_constraints c
                JOIN information_schema.key_column_usage x
                    on x.constraint_name = c.constraint_name
                JOIN information_schema.key_column_usage y
                    on y.ordinal_position = x.position_in_unique_constraint
                    and y.constraint_name = c.unique_constraint_name
                WHERE x.table_name = ' . $this->escape($table) .
                'order by c.constraint_name, x.ordinal_position';

        if (($query = $this->query($sql)) === false) {
            throw new DatabaseException(lang('Database.failGetForeignKeyData'));
        }

        $query   = $query->getResultObject();
        $indexes = [];

        foreach ($query as $row) {
            $indexes[$row->constraint_name]['constraint_name']       = $row->constraint_name;
            $indexes[$row->constraint_name]['table_name']            = $table;
            $indexes[$row->constraint_name]['column_name'][]         = $row->column_name;
            $indexes[$row->constraint_name]['foreign_table_name']    = $row->foreign_table_name;
            $indexes[$row->constraint_name]['foreign_column_name'][] = $row->foreign_column_name;
            $indexes[$row->constraint_name]['on_delete']             = $row->delete_rule;
            $indexes[$row->constraint_name]['on_update']             = $row->update_rule;
            $indexes[$row->constraint_name]['match']                 = $row->match_option;
        }

        return $this->foreignKeyDataToObjects($indexes);
    }

    
    protected function _disableForeignKeyChecks()
    {
        return 'SET CONSTRAINTS ALL DEFERRED';
    }

    
    protected function _enableForeignKeyChecks()
    {
        return 'SET CONSTRAINTS ALL IMMEDIATE;';
    }

    
    public function error(): array
    {
        return [
            'code'    => '',
            'message' => pg_last_error($this->connID),
        ];
    }

    
    public function insertID()
    {
        $v = pg_version($this->connID);
        
        $v = explode(' ', $v['server'])[0] ?? 0;

        $table  = func_num_args() > 0 ? func_get_arg(0) : null;
        $column = func_num_args() > 1 ? func_get_arg(1) : null;

        if ($table === null && $v >= '8.1') {
            $sql = 'SELECT LASTVAL() AS ins_id';
        } elseif ($table !== null) {
            if ($column !== null && $v >= '8.0') {
                $sql   = "SELECT pg_get_serial_sequence('{$table}', '{$column}') AS seq";
                $query = $this->query($sql);
                $query = $query->getRow();
                $seq   = $query->seq;
            } else {
                
                $seq = $table;
            }

            $sql = "SELECT CURRVAL('{$seq}') AS ins_id";
        } else {
            return pg_last_oid($this->resultID);
        }

        $query = $this->query($sql);
        $query = $query->getRow();

        return (int) $query->ins_id;
    }

    
    protected function buildDSN()
    {
        if ($this->DSN !== '') {
            $this->DSN = '';
        }

        
        if (str_contains($this->hostname, '/')) {
            $this->port = '';
        }

        if ($this->hostname !== '') {
            $this->DSN = "host={$this->hostname} ";
        }

        
        $port = (string) $this->port;

        if ($port !== '' && ctype_digit($port)) {
            $this->DSN .= "port={$port} ";
        }

        if ($this->username !== '') {
            $this->DSN .= "user={$this->username} ";

            
            
            if ($this->password !== null) {
                $this->DSN .= "password='{$this->password}' ";
            }
        }

        if ($this->database !== '') {
            $this->DSN .= "dbname={$this->database} ";
        }

        
        
        
        
        
        foreach (['connect_timeout', 'options', 'sslmode', 'service'] as $key) {
            if (isset($this->{$key}) && is_string($this->{$key}) && $this->{$key} !== '') {
                $this->DSN .= "{$key}='{$this->{$key}}' ";
            }
        }

        $this->DSN = rtrim($this->DSN);
    }

    
    protected function setClientEncoding(string $charset): bool
    {
        return pg_set_client_encoding($this->connID, $charset) === 0;
    }

    
    protected function _transBegin(): bool
    {
        return (bool) pg_query($this->connID, 'BEGIN');
    }

    
    protected function _transCommit(): bool
    {
        return (bool) pg_query($this->connID, 'COMMIT');
    }

    
    protected function _transRollback(): bool
    {
        return (bool) pg_query($this->connID, 'ROLLBACK');
    }
}
