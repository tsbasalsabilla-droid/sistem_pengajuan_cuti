<?php

declare(strict_types=1);



namespace CodeIgniter\Database\Postgre;

use CodeIgniter\Database\Forge as BaseForge;


class Forge extends BaseForge
{
    
    protected $checkDatabaseExistStr = 'SELECT 1 FROM pg_database WHERE datname = ?';

    
    protected $dropConstraintStr = 'ALTER TABLE %s DROP CONSTRAINT %s';

    
    protected $dropIndexStr = 'DROP INDEX %s';

    
    protected $_unsigned = [
        'INT2'     => 'INTEGER',
        'SMALLINT' => 'INTEGER',
        'INT'      => 'BIGINT',
        'INT4'     => 'BIGINT',
        'INTEGER'  => 'BIGINT',
        'INT8'     => 'NUMERIC',
        'BIGINT'   => 'NUMERIC',
        'REAL'     => 'DOUBLE PRECISION',
        'FLOAT'    => 'DOUBLE PRECISION',
    ];

    
    protected $null = 'NULL';

    
    protected $db;

    
    protected function _createTableAttributes(array $attributes): string
    {
        return '';
    }

    
    protected function _alterTable(string $alterType, string $table, $processedFields)
    {
        if (in_array($alterType, ['DROP', 'ADD'], true)) {
            return parent::_alterTable($alterType, $table, $processedFields);
        }

        $sql  = 'ALTER TABLE ' . $this->db->escapeIdentifiers($table);
        $sqls = [];

        foreach ($processedFields as $field) {
            if ($field['_literal'] !== false) {
                return false;
            }

            if (version_compare($this->db->getVersion(), '8', '>=') && isset($field['type'])) {
                $sqls[] = $sql . ' ALTER COLUMN ' . $this->db->escapeIdentifiers($field['name'])
                    . " TYPE {$field['type']}{$field['length']}";
            }

            if (! empty($field['default'])) {
                $sqls[] = $sql . ' ALTER COLUMN ' . $this->db->escapeIdentifiers($field['name'])
                    . " SET {$field['default']}";
            }

            $nullable = true; 
            if (isset($field['null']) && ($field['null'] === false || $field['null'] === ' NOT ' . $this->null)) {
                $nullable = false;
            }
            $sqls[] = $sql . ' ALTER COLUMN ' . $this->db->escapeIdentifiers($field['name'])
                . ($nullable ? ' DROP' : ' SET') . ' NOT NULL';

            if (! empty($field['new_name'])) {
                $sqls[] = $sql . ' RENAME COLUMN ' . $this->db->escapeIdentifiers($field['name'])
                    . ' TO ' . $this->db->escapeIdentifiers($field['new_name']);
            }

            if (! empty($field['comment'])) {
                $sqls[] = 'COMMENT ON COLUMN' . $this->db->escapeIdentifiers($table)
                    . '.' . $this->db->escapeIdentifiers($field['name'])
                    . " IS {$field['comment']}";
            }
        }

        return $sqls;
    }

    
    protected function _processColumn(array $processedField): string
    {
        return $this->db->escapeIdentifiers($processedField['name'])
            . ' ' . $processedField['type'] . ($processedField['type'] === 'text' ? '' : $processedField['length'])
            . $processedField['default']
            . $processedField['null']
            . $processedField['auto_increment']
            . $processedField['unique'];
    }

    
    protected function _attributeType(array &$attributes)
    {
        
        if (isset($attributes['CONSTRAINT']) && str_contains(strtolower($attributes['TYPE']), 'int')) {
            $attributes['CONSTRAINT'] = null;
        }

        switch (strtoupper($attributes['TYPE'])) {
            case 'TINYINT':
                $attributes['TYPE']     = 'SMALLINT';
                $attributes['UNSIGNED'] = false;
                break;

            case 'MEDIUMINT':
                $attributes['TYPE']     = 'INTEGER';
                $attributes['UNSIGNED'] = false;
                break;

            case 'DATETIME':
                $attributes['TYPE'] = 'TIMESTAMP';
                break;

            case 'BLOB':
                $attributes['TYPE'] = 'BYTEA';
                break;

            default:
                break;
        }
    }

    
    protected function _attributeAutoIncrement(array &$attributes, array &$field)
    {
        if (! empty($attributes['AUTO_INCREMENT']) && $attributes['AUTO_INCREMENT'] === true) {
            $field['type'] = $field['type'] === 'NUMERIC' || $field['type'] === 'BIGINT' ? 'BIGSERIAL' : 'SERIAL';
        }
    }

    
    protected function _dropTable(string $table, bool $ifExists, bool $cascade): string
    {
        $sql = parent::_dropTable($table, $ifExists, $cascade);

        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $sql;
    }

    
    protected function _dropKeyAsConstraint(string $table, string $constraintName): string
    {
        return "SELECT con.conname
               FROM pg_catalog.pg_constraint con
                INNER JOIN pg_catalog.pg_class rel
                           ON rel.oid = con.conrelid
                INNER JOIN pg_catalog.pg_namespace nsp
                           ON nsp.oid = connamespace
               WHERE nsp.nspname = '{$this->db->schema}'
                     AND rel.relname = '" . trim($table, '"') . "'
                     AND con.conname = '" . trim($constraintName, '"') . "'";
    }
}
