<?php

declare(strict_types=1);



namespace CodeIgniter\Database\MySQLi;

use CodeIgniter\Database\Forge as BaseForge;


class Forge extends BaseForge
{
    
    protected $createDatabaseStr = 'CREATE DATABASE %s CHARACTER SET %s COLLATE %s';

    
    protected $createDatabaseIfStr = 'CREATE DATABASE IF NOT EXISTS %s CHARACTER SET %s COLLATE %s';

    
    protected $dropConstraintStr = 'ALTER TABLE %s DROP FOREIGN KEY %s';

    
    protected $createTableKeys = true;

    
    protected $_unsigned = [
        'TINYINT',
        'SMALLINT',
        'MEDIUMINT',
        'INT',
        'INTEGER',
        'BIGINT',
        'REAL',
        'DOUBLE',
        'DOUBLE PRECISION',
        'FLOAT',
        'DECIMAL',
        'NUMERIC',
    ];

    
    protected $_quoted_table_options = [
        'COMMENT',
        'COMPRESSION',
        'CONNECTION',
        'DATA DIRECTORY',
        'INDEX DIRECTORY',
        'ENCRYPTION',
        'PASSWORD',
    ];

    
    protected $null = 'NULL';

    
    protected function _createTableAttributes(array $attributes): string
    {
        $sql = '';

        foreach (array_keys($attributes) as $key) {
            if (is_string($key)) {
                $sql .= ' ' . strtoupper($key) . ' = ';

                if (in_array(strtoupper($key), $this->_quoted_table_options, true)) {
                    $sql .= $this->db->escape($attributes[$key]);
                } else {
                    $sql .= $this->db->escapeString($attributes[$key]);
                }
            }
        }

        if ($this->db->charset !== '' && ! str_contains($sql, 'CHARACTER SET') && ! str_contains($sql, 'CHARSET')) {
            $sql .= ' DEFAULT CHARACTER SET = ' . $this->db->escapeString($this->db->charset);
        }

        if ($this->db->DBCollat !== '' && ! str_contains($sql, 'COLLATE')) {
            $sql .= ' COLLATE = ' . $this->db->escapeString($this->db->DBCollat);
        }

        return $sql;
    }

    
    protected function _alterTable(string $alterType, string $table, $processedFields)
    {
        if ($alterType === 'DROP') {
            return parent::_alterTable($alterType, $table, $processedFields);
        }

        $sql = 'ALTER TABLE ' . $this->db->escapeIdentifiers($table);

        foreach ($processedFields as $i => $field) {
            if ($field['_literal'] !== false) {
                $processedFields[$i] = ($alterType === 'ADD') ? "\n\tADD " . $field['_literal'] : "\n\tMODIFY " . $field['_literal'];
            } else {
                if ($alterType === 'ADD') {
                    $processedFields[$i]['_literal'] = "\n\tADD ";
                } else {
                    $processedFields[$i]['_literal'] = empty($field['new_name']) ? "\n\tMODIFY " : "\n\tCHANGE ";
                }

                $processedFields[$i] = $processedFields[$i]['_literal'] . $this->_processColumn($processedFields[$i]);
            }
        }

        return [$sql . implode(',', $processedFields)];
    }

    
    protected function _processColumn(array $processedField): string
    {
        $extraClause = isset($processedField['after']) ? ' AFTER ' . $this->db->escapeIdentifiers($processedField['after']) : '';

        if (empty($extraClause) && isset($processedField['first']) && $processedField['first'] === true) {
            $extraClause = ' FIRST';
        }

        return $this->db->escapeIdentifiers($processedField['name'])
                . (empty($processedField['new_name']) ? '' : ' ' . $this->db->escapeIdentifiers($processedField['new_name']))
                . ' ' . $processedField['type'] . $processedField['length']
                . $processedField['unsigned']
                . $processedField['null']
                . $processedField['default']
                . $processedField['auto_increment']
                . $processedField['unique']
                . (empty($processedField['comment']) ? '' : ' COMMENT ' . $processedField['comment'])
                . $extraClause;
    }

    
    protected function _processIndexes(string $table, bool $asQuery = false): array
    {
        $sqls  = [''];
        $index = 0;

        for ($i = 0, $c = count($this->keys); $i < $c; $i++) {
            $index = $i;
            if ($asQuery === false) {
                $index = 0;
            }

            if (isset($this->keys[$i]['fields'])) {
                for ($i2 = 0, $c2 = count($this->keys[$i]['fields']); $i2 < $c2; $i2++) {
                    if (! isset($this->fields[$this->keys[$i]['fields'][$i2]])) {
                        unset($this->keys[$i]['fields'][$i2]);

                        continue;
                    }
                }
            }

            if (! is_array($this->keys[$i]['fields'])) {
                $this->keys[$i]['fields'] = [$this->keys[$i]['fields']];
            }

            $unique = in_array($i, $this->uniqueKeys, true) ? 'UNIQUE ' : '';

            $keyName = $this->db->escapeIdentifiers(($this->keys[$i]['keyName'] === '') ?
                implode('_', $this->keys[$i]['fields']) :
                $this->keys[$i]['keyName']);

            if ($asQuery) {
                $sqls[$index] = 'ALTER TABLE ' . $this->db->escapeIdentifiers($table) . " ADD {$unique}KEY "
                    . $keyName
                    . ' (' . implode(', ', $this->db->escapeIdentifiers($this->keys[$i]['fields'])) . ')';
            } else {
                $sqls[$index] .= ",\n\t{$unique}KEY " . $keyName
                . ' (' . implode(', ', $this->db->escapeIdentifiers($this->keys[$i]['fields'])) . ')';
            }
        }

        $this->keys = [];

        return $sqls;
    }

    
    public function dropKey(string $table, string $keyName, bool $prefixKeyName = true): bool
    {
        $sql = sprintf(
            $this->dropIndexStr,
            $this->db->escapeIdentifiers($keyName),
            $this->db->escapeIdentifiers($this->db->DBPrefix . $table),
        );

        return $this->db->query($sql);
    }

    
    public function dropPrimaryKey(string $table, string $keyName = ''): bool
    {
        $sql = sprintf(
            'ALTER TABLE %s DROP PRIMARY KEY',
            $this->db->escapeIdentifiers($this->db->DBPrefix . $table),
        );

        return $this->db->query($sql);
    }
}
