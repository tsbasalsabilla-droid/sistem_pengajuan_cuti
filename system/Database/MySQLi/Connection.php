<?php

declare(strict_types=1);



namespace CodeIgniter\Database\MySQLi;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\TableName;
use CodeIgniter\Exceptions\LogicException;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use stdClass;
use Throwable;


class Connection extends BaseConnection
{
    
    public $DBDriver = 'MySQLi';

    
    public $deleteHack = true;

    
    public $escapeChar = '`';

    
    public $mysqli;

    
    public $resultMode = MYSQLI_STORE_RESULT;

    
    public $numberNative = false;

    
    public $foundRows = false;

    
    public function connect(bool $persistent = false)
    {
        
        if ($this->hostname[0] === '/') {
            $hostname = null;
            $port     = null;
            $socket   = $this->hostname;
        } else {
            $hostname = $persistent ? 'p:' . $this->hostname : $this->hostname;
            $port     = empty($this->port) ? null : $this->port;
            $socket   = '';
        }

        $clientFlags  = ($this->compress === true) ? MYSQLI_CLIENT_COMPRESS : 0;
        $this->mysqli = mysqli_init();

        mysqli_report(MYSQLI_REPORT_ALL & ~MYSQLI_REPORT_INDEX);

        $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

        if ($this->numberNative === true) {
            $this->mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
        }

        if ($this->strictOn !== null) {
            if ($this->strictOn) {
                $this->mysqli->options(
                    MYSQLI_INIT_COMMAND,
                    "SET SESSION sql_mode = CONCAT(@@sql_mode, ',', 'STRICT_ALL_TABLES')",
                );
            } else {
                $this->mysqli->options(
                    MYSQLI_INIT_COMMAND,
                    "SET SESSION sql_mode = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                                        @@sql_mode,
                                        'STRICT_ALL_TABLES,', ''),
                                    ',STRICT_ALL_TABLES', ''),
                                'STRICT_ALL_TABLES', ''),
                            'STRICT_TRANS_TABLES,', ''),
                        ',STRICT_TRANS_TABLES', ''),
                    'STRICT_TRANS_TABLES', '')",
                );
            }
        }

        if (is_array($this->encrypt)) {
            $ssl = [];

            if (! empty($this->encrypt['ssl_key'])) {
                $ssl['key'] = $this->encrypt['ssl_key'];
            }
            if (! empty($this->encrypt['ssl_cert'])) {
                $ssl['cert'] = $this->encrypt['ssl_cert'];
            }
            if (! empty($this->encrypt['ssl_ca'])) {
                $ssl['ca'] = $this->encrypt['ssl_ca'];
            }
            if (! empty($this->encrypt['ssl_capath'])) {
                $ssl['capath'] = $this->encrypt['ssl_capath'];
            }
            if (! empty($this->encrypt['ssl_cipher'])) {
                $ssl['cipher'] = $this->encrypt['ssl_cipher'];
            }

            if ($ssl !== []) {
                if (isset($this->encrypt['ssl_verify'])) {
                    if ($this->encrypt['ssl_verify']) {
                        if (defined('MYSQLI_OPT_SSL_VERIFY_SERVER_CERT')) {
                            $this->mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, 1);
                        }
                    }
                    
                    
                    
                    
                    
                    
                    elseif (defined('MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT') && version_compare($this->mysqli->client_info, 'mysqlnd 5.6', '>=')) {
                        $clientFlags += MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
                    }
                }

                $this->mysqli->ssl_set(
                    $ssl['key'] ?? null,
                    $ssl['cert'] ?? null,
                    $ssl['ca'] ?? null,
                    $ssl['capath'] ?? null,
                    $ssl['cipher'] ?? null,
                );
            }

            $clientFlags += MYSQLI_CLIENT_SSL;
        }

        if ($this->foundRows) {
            $clientFlags += MYSQLI_CLIENT_FOUND_ROWS;
        }

        try {
            if ($this->mysqli->real_connect(
                $hostname,
                $this->username,
                $this->password,
                $this->database,
                $port,
                $socket,
                $clientFlags,
            )) {
                if (! $this->mysqli->set_charset($this->charset)) {
                    log_message('error', "Database: Unable to set the configured connection charset ('{$this->charset}').");

                    $this->mysqli->close();

                    if ($this->DBDebug) {
                        throw new DatabaseException('Unable to set client connection character set: ' . $this->charset);
                    }

                    return false;
                }

                return $this->mysqli;
            }
        } catch (Throwable $e) {
            
            $msg = $e->getMessage();

            $msg = str_replace($this->username, '****', $msg);
            $msg = str_replace($this->password, '****', $msg);

            throw new DatabaseException($msg, $e->getCode(), $e);
        }

        return false;
    }

    
    protected function _close()
    {
        $this->connID->close();
    }

    
    public function setDatabase(string $databaseName): bool
    {
        if ($databaseName === '') {
            $databaseName = $this->database;
        }

        if (empty($this->connID)) {
            $this->initialize();
        }

        if ($this->connID->select_db($databaseName)) {
            $this->database = $databaseName;

            return true;
        }

        return false;
    }

    
    public function getVersion(): string
    {
        if (isset($this->dataCache['version'])) {
            return $this->dataCache['version'];
        }

        if (empty($this->mysqli)) {
            $this->initialize();
        }

        return $this->dataCache['version'] = $this->mysqli->server_info;
    }

    
    protected function execute(string $sql)
    {
        while ($this->connID->more_results()) {
            $this->connID->next_result();
            if ($res = $this->connID->store_result()) {
                $res->free();
            }
        }

        try {
            return $this->connID->query($this->prepQuery($sql), $this->resultMode);
        } catch (mysqli_sql_exception $e) {
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

    
    protected function prepQuery(string $sql): string
    {
        
        
        if ($this->deleteHack === true && preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql)) {
            return trim($sql) . ' WHERE 1=1';
        }

        return $sql;
    }

    
    public function affectedRows(): int
    {
        return $this->connID->affected_rows ?? 0;
    }

    
    protected function _escapeString(string $str): string
    {
        if (! $this->connID) {
            $this->initialize();
        }

        return $this->connID->real_escape_string($str);
    }

    
    public function escapeLikeStringDirect($str)
    {
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = $this->escapeLikeStringDirect($val);
            }

            return $str;
        }

        $str = $this->_escapeString($str);

        
        return str_replace(
            [$this->likeEscapeChar, '%', '_'],
            ['\\' . $this->likeEscapeChar, '\\%', '\\_'],
            $str,
        );
    }

    
    protected function _listTables(bool $prefixLimit = false, ?string $tableName = null): string
    {
        $sql = 'SHOW TABLES FROM ' . $this->escapeIdentifier($this->database);

        if ((string) $tableName !== '') {
            return $sql . ' LIKE ' . $this->escape($tableName);
        }

        if ($prefixLimit && $this->DBPrefix !== '') {
            return $sql . " LIKE '" . $this->escapeLikeStringDirect($this->DBPrefix) . "%'";
        }

        return $sql;
    }

    
    protected function _listColumns($table = ''): string
    {
        $tableName = $this->protectIdentifiers(
            $table,
            true,
            null,
            false,
        );

        return 'SHOW COLUMNS FROM ' . $tableName;
    }

    
    protected function _fieldData(string $table): array
    {
        $table = $this->protectIdentifiers($table, true, null, false);

        if (($query = $this->query('SHOW COLUMNS FROM ' . $table)) === false) {
            throw new DatabaseException(lang('Database.failGetFieldData'));
        }
        $query = $query->getResultObject();

        $retVal = [];

        for ($i = 0, $c = count($query); $i < $c; $i++) {
            $retVal[$i]       = new stdClass();
            $retVal[$i]->name = $query[$i]->Field;

            sscanf($query[$i]->Type, '%[a-z](%d)', $retVal[$i]->type, $retVal[$i]->max_length);

            $retVal[$i]->nullable    = $query[$i]->Null === 'YES';
            $retVal[$i]->default     = $query[$i]->Default;
            $retVal[$i]->primary_key = (int) ($query[$i]->Key === 'PRI');
        }

        return $retVal;
    }

    
    protected function _indexData(string $table): array
    {
        $table = $this->protectIdentifiers($table, true, null, false);

        if (($query = $this->query('SHOW INDEX FROM ' . $table)) === false) {
            throw new DatabaseException(lang('Database.failGetIndexData'));
        }

        $indexes = $query->getResultArray();

        if ($indexes === []) {
            return [];
        }

        $keys = [];

        foreach ($indexes as $index) {
            if (empty($keys[$index['Key_name']])) {
                $keys[$index['Key_name']]       = new stdClass();
                $keys[$index['Key_name']]->name = $index['Key_name'];

                if ($index['Key_name'] === 'PRIMARY') {
                    $type = 'PRIMARY';
                } elseif ($index['Index_type'] === 'FULLTEXT') {
                    $type = 'FULLTEXT';
                } elseif ($index['Non_unique']) {
                    $type = $index['Index_type'] === 'SPATIAL' ? 'SPATIAL' : 'INDEX';
                } else {
                    $type = 'UNIQUE';
                }

                $keys[$index['Key_name']]->type = $type;
            }

            $keys[$index['Key_name']]->fields[] = $index['Column_name'];
        }

        return $keys;
    }

    
    protected function _foreignKeyData(string $table): array
    {
        $sql = '
                SELECT
                    tc.CONSTRAINT_NAME,
                    tc.TABLE_NAME,
                    kcu.COLUMN_NAME,
                    rc.REFERENCED_TABLE_NAME,
                    kcu.REFERENCED_COLUMN_NAME,
                    rc.DELETE_RULE,
                    rc.UPDATE_RULE,
                    rc.MATCH_OPTION
                FROM information_schema.table_constraints AS tc
                INNER JOIN information_schema.referential_constraints AS rc
                    ON tc.constraint_name = rc.constraint_name
                    AND tc.constraint_schema = rc.constraint_schema
                INNER JOIN information_schema.key_column_usage AS kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.constraint_schema = kcu.constraint_schema
                WHERE
                    tc.constraint_type = ' . $this->escape('FOREIGN KEY') . ' AND
                    tc.table_schema = ' . $this->escape($this->database) . ' AND
                    tc.table_name = ' . $this->escape($table);

        if (($query = $this->query($sql)) === false) {
            throw new DatabaseException(lang('Database.failGetForeignKeyData'));
        }

        $query   = $query->getResultObject();
        $indexes = [];

        foreach ($query as $row) {
            $indexes[$row->CONSTRAINT_NAME]['constraint_name']       = $row->CONSTRAINT_NAME;
            $indexes[$row->CONSTRAINT_NAME]['table_name']            = $row->TABLE_NAME;
            $indexes[$row->CONSTRAINT_NAME]['column_name'][]         = $row->COLUMN_NAME;
            $indexes[$row->CONSTRAINT_NAME]['foreign_table_name']    = $row->REFERENCED_TABLE_NAME;
            $indexes[$row->CONSTRAINT_NAME]['foreign_column_name'][] = $row->REFERENCED_COLUMN_NAME;
            $indexes[$row->CONSTRAINT_NAME]['on_delete']             = $row->DELETE_RULE;
            $indexes[$row->CONSTRAINT_NAME]['on_update']             = $row->UPDATE_RULE;
            $indexes[$row->CONSTRAINT_NAME]['match']                 = $row->MATCH_OPTION;
        }

        return $this->foreignKeyDataToObjects($indexes);
    }

    
    protected function _disableForeignKeyChecks()
    {
        return 'SET FOREIGN_KEY_CHECKS=0';
    }

    
    protected function _enableForeignKeyChecks()
    {
        return 'SET FOREIGN_KEY_CHECKS=1';
    }

    
    public function error(): array
    {
        if (! empty($this->mysqli->connect_errno)) {
            return [
                'code'    => $this->mysqli->connect_errno,
                'message' => $this->mysqli->connect_error,
            ];
        }

        return [
            'code'    => $this->connID->errno,
            'message' => $this->connID->error,
        ];
    }

    
    public function insertID(): int
    {
        return $this->connID->insert_id;
    }

    
    protected function _transBegin(): bool
    {
        return $this->connID->begin_transaction();
    }

    
    protected function _transCommit(): bool
    {
        return $this->connID->commit();
    }

    
    protected function _transRollback(): bool
    {
        return $this->connID->rollback();
    }
}
