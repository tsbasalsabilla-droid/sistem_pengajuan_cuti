<?php

declare(strict_types=1);



namespace CodeIgniter\Database\OCI8;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Query;
use CodeIgniter\Database\TableName;
use ErrorException;
use stdClass;


class Connection extends BaseConnection
{
    
    protected $DBDriver = 'OCI8';

    
    public $escapeChar = '"';

    
    protected $reservedIdentifiers = [
        '*',
        'rownum',
    ];

    protected $validDSNs = [
        
        'tns' => '/^\(DESCRIPTION=(\(.+\)){2,}\)$/',
        
        
        
        'ec' => '/^
            (\/\/)?
            (\[)?[a-z0-9.:_-]+(\])? # Host or IP address
            (:[1-9][0-9]{0,4})?     # Port
            (
                (\/)
                ([a-z0-9.$_]+)?     # Service name
                (:[a-z]+)?          # Server type
                (\/[a-z0-9$_]+)?    # Instance name
            )?
        $/ix',
        
        'in' => '/^[a-z0-9$_]+$/i',
    ];

    
    protected $resetStmtId = true;

    
    protected $stmtId;

    
    public $commitMode = OCI_COMMIT_ON_SUCCESS;

    
    protected $cursorId;

    
    public $lastInsertedTableName;

    
    private function isValidDSN(): bool
    {
        if ($this->DSN === null || $this->DSN === '') {
            return false;
        }

        foreach ($this->validDSNs as $regexp) {
            if (preg_match($regexp, $this->DSN)) {
                return true;
            }
        }

        return false;
    }

    
    public function connect(bool $persistent = false)
    {
        if (! $this->isValidDSN()) {
            $this->buildDSN();
        }

        $func = $persistent ? 'oci_pconnect' : 'oci_connect';

        return ($this->charset === '')
            ? $func($this->username, $this->password, $this->DSN)
            : $func($this->username, $this->password, $this->DSN, $this->charset);
    }

    
    protected function _close()
    {
        if (is_resource($this->cursorId)) {
            oci_free_statement($this->cursorId);
        }
        if (is_resource($this->stmtId)) {
            oci_free_statement($this->stmtId);
        }
        oci_close($this->connID);
    }

    
    protected function _ping(): bool
    {
        try {
            $result = $this->simpleQuery('SELECT 1 FROM DUAL');

            return $result !== false;
        } catch (DatabaseException) {
            return false;
        }
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

        if ($this->connID === false) {
            $this->initialize();
        }

        if (($versionString = oci_server_version($this->connID)) === false) {
            return '';
        }

        if (preg_match('#Release\s(\d+(?:\.\d+)+)#', $versionString, $match)) {
            return $this->dataCache['version'] = $match[1];
        }

        return '';
    }

    
    protected function execute(string $sql)
    {
        try {
            if ($this->resetStmtId === true) {
                $this->stmtId = oci_parse($this->connID, $sql);
            }

            oci_set_prefetch($this->stmtId, 1000);

            $result          = oci_execute($this->stmtId, $this->commitMode) ? $this->stmtId : false;
            $insertTableName = $this->parseInsertTableName($sql);

            if ($result && $insertTableName !== '') {
                $this->lastInsertedTableName = $insertTableName;
            }

            return $result;
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

    
    public function parseInsertTableName(string $sql): string
    {
        $commentStrippedSql = preg_replace(['/\/\*(.|\n)*?\*\//m', '/--.+/'], '', $sql);
        $isInsertQuery      = str_starts_with(strtoupper(ltrim($commentStrippedSql)), 'INSERT');

        if (! $isInsertQuery) {
            return '';
        }

        preg_match('/(?is)\b(?:into)\s+("?\w+"?)/', $commentStrippedSql, $match);
        $tableName = $match[1] ?? '';

        return str_starts_with($tableName, '"') ? trim($tableName, '"') : strtoupper($tableName);
    }

    
    public function affectedRows(): int
    {
        return oci_num_rows($this->stmtId);
    }

    
    protected function _listTables(bool $prefixLimit = false, ?string $tableName = null): string
    {
        $sql = 'SELECT "TABLE_NAME" FROM "USER_TABLES"';

        if ($tableName !== null) {
            return $sql . ' WHERE "TABLE_NAME" LIKE ' . $this->escape($tableName);
        }

        if ($prefixLimit && $this->DBPrefix !== '') {
            return $sql . ' WHERE "TABLE_NAME" LIKE \'' . $this->escapeLikeString($this->DBPrefix) . "%' "
                    . sprintf($this->likeEscapeStr, $this->likeEscapeChar);
        }

        return $sql;
    }

    
    protected function _listColumns($table = ''): string
    {
        if ($table instanceof TableName) {
            $tableName = $this->escape(strtoupper($table->getActualTableName()));
            $owner     = $this->username;
        } elseif (str_contains($table, '.')) {
            sscanf($table, '%[^.].%s', $owner, $tableName);
            $tableName = $this->escape(strtoupper($this->DBPrefix . $tableName));
        } else {
            $owner     = $this->username;
            $tableName = $this->escape(strtoupper($this->DBPrefix . $table));
        }

        return 'SELECT COLUMN_NAME FROM ALL_TAB_COLUMNS
			WHERE UPPER(OWNER) = ' . $this->escape(strtoupper($owner)) . '
				AND UPPER(TABLE_NAME) = ' . $tableName;
    }

    
    protected function _fieldData(string $table): array
    {
        if (str_contains($table, '.')) {
            sscanf($table, '%[^.].%s', $owner, $table);
        } else {
            $owner = $this->username;
        }

        $sql = 'SELECT COLUMN_NAME, DATA_TYPE, CHAR_LENGTH, DATA_PRECISION, DATA_LENGTH, DATA_DEFAULT, NULLABLE
			FROM ALL_TAB_COLUMNS
			WHERE UPPER(OWNER) = ' . $this->escape(strtoupper($owner)) . '
				AND UPPER(TABLE_NAME) = ' . $this->escape(strtoupper($table));

        if (($query = $this->query($sql)) === false) {
            throw new DatabaseException(lang('Database.failGetFieldData'));
        }
        $query = $query->getResultObject();

        $retval = [];

        for ($i = 0, $c = count($query); $i < $c; $i++) {
            $retval[$i]       = new stdClass();
            $retval[$i]->name = $query[$i]->COLUMN_NAME;
            $retval[$i]->type = $query[$i]->DATA_TYPE;

            $length = $query[$i]->CHAR_LENGTH > 0 ? $query[$i]->CHAR_LENGTH : $query[$i]->DATA_PRECISION;
            $length ??= $query[$i]->DATA_LENGTH;

            $retval[$i]->max_length = $length;

            $retval[$i]->nullable = $query[$i]->NULLABLE === 'Y';
            $retval[$i]->default  = $this->normalizeDefault($query[$i]->DATA_DEFAULT);
        }

        return $retval;
    }

    
    private function normalizeDefault(?string $default): ?string
    {
        if ($default === null) {
            return $default;
        }

        return rtrim($default);
    }

    
    protected function _indexData(string $table): array
    {
        if (str_contains($table, '.')) {
            sscanf($table, '%[^.].%s', $owner, $table);
        } else {
            $owner = $this->username;
        }

        $sql = 'SELECT AIC.INDEX_NAME, UC.CONSTRAINT_TYPE, AIC.COLUMN_NAME '
            . ' FROM ALL_IND_COLUMNS AIC '
            . ' LEFT JOIN USER_CONSTRAINTS UC ON AIC.INDEX_NAME = UC.CONSTRAINT_NAME AND AIC.TABLE_NAME = UC.TABLE_NAME '
            . 'WHERE AIC.TABLE_NAME = ' . $this->escape(strtolower($table)) . ' '
            . 'AND AIC.TABLE_OWNER = ' . $this->escape(strtoupper($owner)) . ' '
            . ' ORDER BY UC.CONSTRAINT_TYPE, AIC.COLUMN_POSITION';

        if (($query = $this->query($sql)) === false) {
            throw new DatabaseException(lang('Database.failGetIndexData'));
        }
        $query = $query->getResultObject();

        $retVal          = [];
        $constraintTypes = [
            'P' => 'PRIMARY',
            'U' => 'UNIQUE',
        ];

        foreach ($query as $row) {
            if (isset($retVal[$row->INDEX_NAME])) {
                $retVal[$row->INDEX_NAME]->fields[] = $row->COLUMN_NAME;

                continue;
            }

            $retVal[$row->INDEX_NAME]         = new stdClass();
            $retVal[$row->INDEX_NAME]->name   = $row->INDEX_NAME;
            $retVal[$row->INDEX_NAME]->fields = [$row->COLUMN_NAME];
            $retVal[$row->INDEX_NAME]->type   = $constraintTypes[$row->CONSTRAINT_TYPE] ?? 'INDEX';
        }

        return $retVal;
    }

    
    protected function _foreignKeyData(string $table): array
    {
        $sql = 'SELECT
                acc.constraint_name,
                acc.table_name,
                acc.column_name,
                ccu.table_name foreign_table_name,
                accu.column_name foreign_column_name,
                ac.delete_rule
                FROM all_cons_columns acc
                JOIN all_constraints ac ON acc.owner = ac.owner
                AND acc.constraint_name = ac.constraint_name
                JOIN all_constraints ccu ON ac.r_owner = ccu.owner
                AND ac.r_constraint_name = ccu.constraint_name
                JOIN all_cons_columns accu ON accu.constraint_name = ccu.constraint_name
                AND accu.position = acc.position
                AND accu.table_name = ccu.table_name
                WHERE ac.constraint_type = ' . $this->escape('R') . '
                AND acc.table_name = ' . $this->escape($table);

        $query = $this->query($sql);

        if ($query === false) {
            throw new DatabaseException(lang('Database.failGetForeignKeyData'));
        }

        $query   = $query->getResultObject();
        $indexes = [];

        foreach ($query as $row) {
            $indexes[$row->CONSTRAINT_NAME]['constraint_name']       = $row->CONSTRAINT_NAME;
            $indexes[$row->CONSTRAINT_NAME]['table_name']            = $row->TABLE_NAME;
            $indexes[$row->CONSTRAINT_NAME]['column_name'][]         = $row->COLUMN_NAME;
            $indexes[$row->CONSTRAINT_NAME]['foreign_table_name']    = $row->FOREIGN_TABLE_NAME;
            $indexes[$row->CONSTRAINT_NAME]['foreign_column_name'][] = $row->FOREIGN_COLUMN_NAME;
            $indexes[$row->CONSTRAINT_NAME]['on_delete']             = $row->DELETE_RULE;
            $indexes[$row->CONSTRAINT_NAME]['on_update']             = null;
            $indexes[$row->CONSTRAINT_NAME]['match']                 = null;
        }

        return $this->foreignKeyDataToObjects($indexes);
    }

    
    protected function _disableForeignKeyChecks()
    {
        return <<<'SQL'
            BEGIN
              FOR c IN
              (SELECT c.owner, c.table_name, c.constraint_name
               FROM user_constraints c, user_tables t
               WHERE c.table_name = t.table_name
               AND c.status = 'ENABLED'
               AND c.constraint_type = 'R'
               AND t.iot_type IS NULL
               ORDER BY c.constraint_type DESC)
              LOOP
                dbms_utility.exec_ddl_statement('alter table "' || c.owner || '"."' || c.table_name || '" disable constraint "' || c.constraint_name || '"');
              END LOOP;
            END;
            SQL;
    }

    
    protected function _enableForeignKeyChecks()
    {
        return <<<'SQL'
            BEGIN
              FOR c IN
              (SELECT c.owner, c.table_name, c.constraint_name
               FROM user_constraints c, user_tables t
               WHERE c.table_name = t.table_name
               AND c.status = 'DISABLED'
               AND c.constraint_type = 'R'
               AND t.iot_type IS NULL
               ORDER BY c.constraint_type DESC)
              LOOP
                dbms_utility.exec_ddl_statement('alter table "' || c.owner || '"."' || c.table_name || '" enable constraint "' || c.constraint_name || '"');
              END LOOP;
            END;
            SQL;
    }

    
    public function getCursor()
    {
        return $this->cursorId = oci_new_cursor($this->connID);
    }

    
    public function storedProcedure(string $procedureName, array $params)
    {
        if ($procedureName === '') {
            throw new DatabaseException(lang('Database.invalidArgument', [$procedureName]));
        }

        
        $sql = sprintf(
            'BEGIN %s (' . substr(str_repeat(',%s', count($params)), 1) . '); END;',
            $procedureName,
            ...array_map(static fn ($row) => $row['name'], $params),
        );

        $this->resetStmtId = false;
        $this->stmtId      = oci_parse($this->connID, $sql);
        $this->bindParams($params);
        $result            = $this->query($sql);
        $this->resetStmtId = true;

        return $result;
    }

    
    protected function bindParams($params)
    {
        if (! is_array($params) || ! is_resource($this->stmtId)) {
            return;
        }

        foreach ($params as $param) {
            oci_bind_by_name(
                $this->stmtId,
                $param['name'],
                $param['value'],
                $param['length'] ?? -1,
                $param['type'] ?? SQLT_CHR,
            );
        }
    }

    
    public function error(): array
    {
        
        
        
        $error     = oci_error();
        $resources = [$this->cursorId, $this->stmtId, $this->connID];

        foreach ($resources as $resource) {
            if (is_resource($resource)) {
                $error = oci_error($resource);
                break;
            }
        }

        return is_array($error)
            ? $error
            : [
                'code'    => '',
                'message' => '',
            ];
    }

    public function insertID(): int
    {
        if (empty($this->lastInsertedTableName)) {
            return 0;
        }

        $indexs     = $this->getIndexData($this->lastInsertedTableName);
        $fieldDatas = $this->getFieldData($this->lastInsertedTableName);

        if ($indexs === [] || $fieldDatas === []) {
            return 0;
        }

        $columnTypeList    = array_column($fieldDatas, 'type', 'name');
        $primaryColumnName = '';

        foreach ($indexs as $index) {
            if ($index->type !== 'PRIMARY' || count($index->fields) !== 1) {
                continue;
            }

            $primaryColumnName = $this->protectIdentifiers($index->fields[0], false, false);
            $primaryColumnType = $columnTypeList[$primaryColumnName];

            if ($primaryColumnType !== 'NUMBER') {
                $primaryColumnName = '';
            }
        }

        if ($primaryColumnName === '') {
            return 0;
        }

        $query           = $this->query('SELECT DATA_DEFAULT FROM USER_TAB_COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?', [$this->lastInsertedTableName, $primaryColumnName])->getRow();
        $lastInsertValue = str_replace('nextval', 'currval', $query->DATA_DEFAULT ?? '0');
        $query           = $this->query(sprintf('SELECT %s SEQ FROM DUAL', $lastInsertValue))->getRow();

        return (int) ($query->SEQ ?? 0);
    }

    
    protected function buildDSN()
    {
        if ($this->DSN !== '') {
            $this->DSN = '';
        }

        
        $this->hostname = str_replace(["\n", "\r", "\t", ' '], '', $this->hostname);

        if (preg_match($this->validDSNs['tns'], $this->hostname)) {
            $this->DSN = $this->hostname;

            return;
        }

        $isEasyConnectableHostName = $this->hostname !== '' && ! str_contains($this->hostname, '/') && ! str_contains($this->hostname, ':');
        $easyConnectablePort       = ($this->port !== '') && ctype_digit((string) $this->port) ? ':' . $this->port : '';
        $easyConnectableDatabase   = $this->database !== '' ? '/' . ltrim($this->database, '/') : '';

        if ($isEasyConnectableHostName && ($easyConnectablePort !== '' || $easyConnectableDatabase !== '')) {
            
            $this->DSN = $this->hostname . $easyConnectablePort . $easyConnectableDatabase;

            if (preg_match($this->validDSNs['ec'], $this->DSN)) {
                return;
            }
        }

        
        if (preg_match($this->validDSNs['ec'], $this->hostname) || preg_match($this->validDSNs['in'], $this->hostname)) {
            $this->DSN = $this->hostname;

            return;
        }

        $this->database = str_replace(["\n", "\r", "\t", ' '], '', $this->database);

        foreach ($this->validDSNs as $regexp) {
            if (preg_match($regexp, $this->database)) {
                return;
            }
        }

        
        $this->DSN = '';
    }

    
    protected function _transBegin(): bool
    {
        $this->commitMode = OCI_NO_AUTO_COMMIT;

        return true;
    }

    
    protected function _transCommit(): bool
    {
        $this->commitMode = OCI_COMMIT_ON_SUCCESS;

        return oci_commit($this->connID);
    }

    
    protected function _transRollback(): bool
    {
        $this->commitMode = OCI_COMMIT_ON_SUCCESS;

        return oci_rollback($this->connID);
    }

    
    public function getDatabase(): string
    {
        if (! empty($this->database)) {
            return $this->database;
        }

        return $this->query('SELECT DEFAULT_TABLESPACE FROM USER_USERS')->getRow()->DEFAULT_TABLESPACE ?? '';
    }

    
    protected function getDriverFunctionPrefix(): string
    {
        return 'oci_';
    }
}
