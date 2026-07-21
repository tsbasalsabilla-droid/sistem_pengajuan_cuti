<?php

declare(strict_types=1);



namespace CodeIgniter\Database\OCI8;

use CodeIgniter\Database\BasePreparedQuery;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\BadMethodCallException;
use OCILob;


class PreparedQuery extends BasePreparedQuery
{
    
    protected $db;

    
    private ?string $lastInsertTableName = null;

    
    public function _prepare(string $sql, array $options = []): PreparedQuery
    {
        if (! $this->statement = oci_parse($this->db->connID, $this->parameterize($sql))) {
            $error             = oci_error($this->db->connID);
            $this->errorCode   = $error['code'] ?? 0;
            $this->errorString = $error['message'] ?? '';

            if ($this->db->DBDebug) {
                throw new DatabaseException($this->errorString . ' code: ' . $this->errorCode);
            }
        }

        $this->lastInsertTableName = $this->db->parseInsertTableName($sql);

        return $this;
    }

    
    public function _execute(array $data): bool
    {
        if (! isset($this->statement)) {
            throw new BadMethodCallException('You must call prepare before trying to execute a prepared statement.');
        }

        $binaryData = null;

        foreach (array_keys($data) as $key) {
            if (is_string($data[$key]) && $this->isBinary($data[$key])) {
                $binaryData = oci_new_descriptor($this->db->connID, OCI_D_LOB);
                $binaryData->writeTemporary($data[$key], OCI_TEMP_BLOB);
                oci_bind_by_name($this->statement, ':' . $key, $binaryData, -1, OCI_B_BLOB);
            } else {
                oci_bind_by_name($this->statement, ':' . $key, $data[$key]);
            }
        }

        $result = oci_execute($this->statement, $this->db->commitMode);

        if ($binaryData instanceof OCILob) {
            $binaryData->free();
        }

        if ($result && $this->lastInsertTableName !== '') {
            $this->db->lastInsertedTableName = $this->lastInsertTableName;
        }

        return $result;
    }

    
    public function _getResult()
    {
        return $this->statement;
    }

    
    protected function _close(): bool
    {
        return oci_free_statement($this->statement);
    }

    
    public function parameterize(string $sql): string
    {
        
        $count = 0;

        return preg_replace_callback('/\?/', static function ($matches) use (&$count): string {
            return ':' . ($count++);
        }, $sql);
    }
}
