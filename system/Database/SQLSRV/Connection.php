<?php

declare(strict_types=1);



namespace CodeIgniter\Database\SQLSRV;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\TableName;
use stdClass;


class Connection extends BaseConnection
{
    
    public $DBDriver = 'SQLSRV';

    
    public $database;

    
    public $scrollable;

    
    public $escapeChar = '"';

    
    public $schema = 'dbo';

    
    protected $_quoted_identifier = true;

    
    protected $_reserved_identifiers = ['*'];

    
    public function __construct(array $params)
    {
        parent::__construct($params);

        
        if ($this->scrollable === null) {
            $this->scrollable = defined('SQLSRV_CURSOR_CLIENT_BUFFERED') ? SQLSRV_CURSOR_CLIENT_BUFFERED : false;
        }
    }

    
    public function connect(bool $persistent = false)
    {
        $charset = in_array(strtolower($this->charset), ['utf-8', 'utf8'], true) ? 'UTF-8' : SQLSRV_ENC_CHAR;

        $connection = [
            'UID'                  => empty($this->username) ? '' : $this->username,
            'PWD'                  => empty($this->password) ? '' : $this->password,
            'Database'             => $this->database,
            'ConnectionPooling'    => $persistent ? 1 : 0,
            'CharacterSet'         => $charset,
            'Encrypt'              => $this->encrypt === true ? 1 : 0,
            'ReturnDatesAsStrings' => 1,
        ];

        
        
        if (empty($connection['UID']) && empty($connection['PWD'])) {
            unset($connection['UID'], $connection['PWD']);
        }

        if (! str_contains($this->hostname, ',') && $this->port !== '') {
            $this->hostname .= ', ' . $this->port;
        }

        sqlsrv_configure('WarningsReturnAsErrors', 0);
        $this->connID = sqlsrv_connect($this->hostname, $connection);

        if ($this->connID !== false) {
            
            $query = $this->query('SELECT CASE WHEN (@@OPTIONS | 256) = @@OPTIONS THEN 1 ELSE 0 END AS qi');
            $query = $query->getResultObject();

            $this->_quoted_identifier = empty($query) ? false : (bool) $query[0]->qi;
            $this->escapeChar         = ($this->_quoted_identifier) ? '"' : ['[', ']'];

            return $this->connID;
        }

        throw new DatabaseException($this->getAllErrorMessages());
    }

    
    public function getAllErrorMessages(): string
    {
        $errors = [];

        foreach (sqlsrv_errors() as $error) {
            $errors[] = sprintf(
                '%s SQLSTATE: %s, code: %s',
                $error['message'],
                $error['SQLSTATE'],
                $error['code'],
            );
        }

        return implode("\n", $errors);
    }

    
    protected function _close()
    {
        sqlsrv_close($this->connID);
    }

    
    protected function _escapeString(string $str): string
    {
        return str_replace("'", "''", remove_invisible_characters($str, false));
    }

    
    public function insertID(): int
    {
        return (int) ($this->query('SELECT SCOPE_IDENTITY() AS insert_id')->getRow()->insert_id ?? 0);
    }

    
    protected function _listTables(bool $prefixLimit = false, ?string $tableName = null): string
    {
        $sql = 'SELECT [TABLE_NAME] AS "name"'
            . ' FROM [INFORMATION_SCHEMA].[TABLES] '
            . ' WHERE '
            . " [TABLE_SCHEMA] = '" . $this->schema . "'    ";

        if ($tableName !== null) {
            return $sql .= ' AND [TABLE_NAME] LIKE ' . $this->escape($tableName);
        }

        if ($prefixLimit && $this->DBPrefix !== '') {
            $sql .= " AND [TABLE_NAME] LIKE '" . $this->escapeLikeString($this->DBPrefix) . "%' "
                . sprintf($this->likeEscapeStr, $this->likeEscapeChar);
        }

        return $sql;
    }

    
    protected function _listColumns($table = ''): string
    {
        if ($table instanceof TableName) {
            $tableName = $this->escape(strtolower($table->getActualTableName()));
        } else {
            $tableName = $this->escape($this->DBPrefix . strtolower($table));
        }

        return 'SELECT [COLUMN_NAME] '
            . ' FROM [INFORMATION_SCHEMA].[COLUMNS]'
            . ' WHERE  [TABLE_NAME] = ' . $tableName
            . ' AND [TABLE_SCHEMA] = ' . $this->escape($this->schema);
    }

    
    protected function _indexData(string $table): array
    {
        $sql = 'EXEC sp_helpindex ' . $this->escape($this->schema . '.' . $table);

        if (($query = $this->query($sql)) === false) {
            throw new DatabaseException(lang('Database.failGetIndexData'));
        }
        $query = $query->getResultObject();

        $retVal = [];

        foreach ($query as $row) {
            $obj       = new stdClass();
            $obj->name = $row->index_name;

            $_fields     = explode(',', trim($row->index_keys));
            $obj->fields = array_map(trim(...), $_fields);

            if (str_contains($row->index_description, 'primary key located on')) {
                $obj->type = 'PRIMARY';
            } else {
                $obj->type = (str_contains($row->index_description, 'nonclustered, unique')) ? 'UNIQUE' : 'INDEX';
            }

            $retVal[$obj->name] = $obj;
        }

        return $retVal;
    }

    
    protected function _foreignKeyData(string $table): array
    {
        $sql = 'SELECT
                f.name as constraint_name,
                OBJECT_NAME (f.parent_object_id) as table_name,
                COL_NAME(fc.parent_object_id,fc.parent_column_id) column_name,
                OBJECT_NAME(f.referenced_object_id) foreign_table_name,
                COL_NAME(fc.referenced_object_id,fc.referenced_column_id) foreign_column_name,
                rc.delete_rule,
                rc.update_rule,
                rc.match_option
                FROM
                sys.foreign_keys AS f
                INNER JOIN sys.foreign_key_columns AS fc ON f.OBJECT_ID = fc.constraint_object_id
                INNER JOIN sys.tables t ON t.OBJECT_ID = fc.referenced_object_id
                INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_NAME = f.name
                WHERE OBJECT_NAME (f.parent_object_id) = ' . $this->escape($table);

        if (($query = $this->query($sql)) === false) {
            throw new DatabaseException(lang('Database.failGetForeignKeyData'));
        }

        $query   = $query->getResultObject();
        $indexes = [];

        foreach ($query as $row) {
            $indexes[$row->constraint_name]['constraint_name']       = $row->constraint_name;
            $indexes[$row->constraint_name]['table_name']            = $row->table_name;
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
        return 'EXEC sp_MSforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT ALL"';
    }

    
    protected function _enableForeignKeyChecks()
    {
        return 'EXEC sp_MSforeachtable "ALTER TABLE ? WITH CHECK CHECK CONSTRAINT ALL"';
    }

    
    protected function _fieldData(string $table): array
    {
        $sql = 'SELECT
                COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION,
                COLUMN_DEFAULT, IS_NULLABLE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME= ' . $this->escape(($table));

        if (($query = $this->query($sql)) === false) {
            throw new DatabaseException(lang('Database.failGetFieldData'));
        }

        $query  = $query->getResultObject();
        $retVal = [];

        for ($i = 0, $c = count($query); $i < $c; $i++) {
            $retVal[$i] = new stdClass();

            $retVal[$i]->name = $query[$i]->COLUMN_NAME;
            $retVal[$i]->type = $query[$i]->DATA_TYPE;

            $retVal[$i]->max_length = $query[$i]->CHARACTER_MAXIMUM_LENGTH > 0
                ? $query[$i]->CHARACTER_MAXIMUM_LENGTH
                : (
                    $query[$i]->CHARACTER_MAXIMUM_LENGTH === -1
                    ? 'max'
                    : $query[$i]->NUMERIC_PRECISION
                );

            $retVal[$i]->nullable = $query[$i]->IS_NULLABLE !== 'NO';
            $retVal[$i]->default  = $this->normalizeDefault($query[$i]->COLUMN_DEFAULT);
        }

        return $retVal;
    }

    
    private function normalizeDefault(?string $default): ?string
    {
        if ($default === null) {
            return null;
        }

        $default = trim($default);

        
        while (preg_match('/^\((.*)\)$/', $default, $matches)) {
            $default = trim($matches[1]);
        }

        
        if (strcasecmp($default, 'NULL') === 0) {
            return null;
        }

        
        if (preg_match("/^'(.*)'$/s", $default, $matches)) {
            return str_replace("''", "'", $matches[1]);
        }

        return $default;
    }

    
    protected function _transBegin(): bool
    {
        return sqlsrv_begin_transaction($this->connID);
    }

    
    protected function _transCommit(): bool
    {
        return sqlsrv_commit($this->connID);
    }

    
    protected function _transRollback(): bool
    {
        return sqlsrv_rollback($this->connID);
    }

    
    public function error(): array
    {
        $error = [
            'code'    => '00000',
            'message' => '',
        ];

        $sqlsrvErrors = sqlsrv_errors(SQLSRV_ERR_ERRORS);

        if (! is_array($sqlsrvErrors)) {
            return $error;
        }

        $sqlsrvError = array_shift($sqlsrvErrors);
        if (isset($sqlsrvError['SQLSTATE'])) {
            $error['code'] = isset($sqlsrvError['code']) ? $sqlsrvError['SQLSTATE'] . '/' . $sqlsrvError['code'] : $sqlsrvError['SQLSTATE'];
        } elseif (isset($sqlsrvError['code'])) {
            $error['code'] = $sqlsrvError['code'];
        }

        if (isset($sqlsrvError['message'])) {
            $error['message'] = $sqlsrvError['message'];
        }

        return $error;
    }

    
    public function affectedRows(): int
    {
        if ($this->resultID === false) {
            return 0;
        }

        return sqlsrv_rows_affected($this->resultID);
    }

    
    public function setDatabase(?string $databaseName = null)
    {
        if ($databaseName === null || $databaseName === '') {
            $databaseName = $this->database;
        }

        if (empty($this->connID)) {
            $this->initialize();
        }

        if ($this->execute('USE ' . $this->_escapeString($databaseName))) {
            $this->database  = $databaseName;
            $this->dataCache = [];

            return true;
        }

        return false;
    }

    
    protected function execute(string $sql)
    {
        $stmt = ($this->scrollable === false || $this->isWriteType($sql))
            ? sqlsrv_query($this->connID, $sql)
            : sqlsrv_query($this->connID, $sql, [], ['Scrollable' => $this->scrollable]);

        if ($stmt === false) {
            $trace = debug_backtrace();
            $first = array_shift($trace);

            log_message('error', "{message}\nin {exFile} on line {exLine}.\n{trace}", [
                'message' => $this->getAllErrorMessages(),
                'exFile'  => clean_path($first['file']),
                'exLine'  => $first['line'],
                'trace'   => render_backtrace($trace),
            ]);

            if ($this->DBDebug) {
                throw new DatabaseException($this->getAllErrorMessages());
            }
        }

        return $stmt;
    }

    
    public function getError()
    {
        $error = [
            'code'    => '00000',
            'message' => '',
        ];

        $sqlsrvErrors = sqlsrv_errors(SQLSRV_ERR_ERRORS);

        if (! is_array($sqlsrvErrors)) {
            return $error;
        }

        $sqlsrvError = array_shift($sqlsrvErrors);
        if (isset($sqlsrvError['SQLSTATE'])) {
            $error['code'] = isset($sqlsrvError['code']) ? $sqlsrvError['SQLSTATE'] . '/' . $sqlsrvError['code'] : $sqlsrvError['SQLSTATE'];
        } elseif (isset($sqlsrvError['code'])) {
            $error['code'] = $sqlsrvError['code'];
        }

        if (isset($sqlsrvError['message'])) {
            $error['message'] = $sqlsrvError['message'];
        }

        return $error;
    }

    
    public function getPlatform(): string
    {
        return $this->DBDriver;
    }

    
    public function getVersion(): string
    {
        $info = [];
        if (isset($this->dataCache['version'])) {
            return $this->dataCache['version'];
        }

        if (! $this->connID) {
            $this->initialize();
        }

        if (($info = sqlsrv_server_info($this->connID)) === []) {
            return '';
        }

        return isset($info['SQLServerVersion']) ? $this->dataCache['version'] = $info['SQLServerVersion'] : '';
    }

    
    public function isWriteType($sql): bool
    {
        if (preg_match('/^\s*"?(EXEC\s*sp_rename)\s/i', $sql)) {
            return true;
        }

        return parent::isWriteType($sql);
    }
}
