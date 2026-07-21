<?php

declare(strict_types=1);



namespace CodeIgniter\Database\SQLSRV;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Forge as BaseForge;
use Throwable;


class Forge extends BaseForge
{
    
    protected $dropConstraintStr;

    
    protected $dropIndexStr;

    
    protected $createDatabaseIfStr = "DECLARE @DBName VARCHAR(255) = '%s'\nDECLARE @SQL VARCHAR(max) = 'IF DB_ID( ''' + @DBName + ''' ) IS NULL CREATE DATABASE %s'\nEXEC( @SQL )";

    
    protected $createDatabaseStr = 'CREATE DATABASE %s ';

    
    protected $checkDatabaseExistStr = 'IF DB_ID( %s ) IS NOT NULL SELECT 1';

    
    protected $renameTableStr;

    
    protected $unsigned = [
        'TINYINT'  => 'SMALLINT',
        'SMALLINT' => 'INT',
        'INT'      => 'BIGINT',
        'REAL'     => 'FLOAT',
    ];

    
    protected $fkAllowActions = ['CASCADE', 'SET NULL', 'NO ACTION', 'RESTRICT', 'SET DEFAULT'];

    
    protected $createTableIfStr;

    
    protected $createTableStr;

    public function __construct(BaseConnection $db)
    {
        parent::__construct($db);

        $this->createTableStr = '%s ' . $this->db->escapeIdentifiers($this->db->schema) . ".%s (%s\n) ";
        $this->renameTableStr = 'EXEC sp_rename [' . $this->db->escapeIdentifiers($this->db->schema) . '.%s] , %s ;';

        $this->dropConstraintStr = 'ALTER TABLE ' . $this->db->escapeIdentifiers($this->db->schema) . '.%s DROP CONSTRAINT %s';
        $this->dropIndexStr      = 'DROP INDEX %s ON ' . $this->db->escapeIdentifiers($this->db->schema) . '.%s';
    }

    
    public function createDatabase(string $dbName, bool $ifNotExists = false): bool
    {
        if ($ifNotExists) {
            $sql = sprintf(
                $this->createDatabaseIfStr,
                $dbName,
                $this->db->escapeIdentifier($dbName),
            );
        } else {
            $sql = sprintf(
                $this->createDatabaseStr,
                $this->db->escapeIdentifier($dbName),
            );
        }

        try {
            if (! $this->db->query($sql)) {
                
                if ($this->db->DBDebug) {
                    throw new DatabaseException('Unable to create the specified database.');
                }

                return false;
                
            }

            if (isset($this->db->dataCache['db_names'])) {
                $this->db->dataCache['db_names'][] = $dbName;
            }

            return true;
        } catch (Throwable $e) {
            if ($this->db->DBDebug) {
                throw new DatabaseException('Unable to create the specified database.', 0, $e);
            }

            return false; 
        }
    }

    
    public function dropDatabase(string $dbName): bool
    {
        try {
            $this->db->query(sprintf(
                'ALTER DATABASE %s SET SINGLE_USER WITH ROLLBACK IMMEDIATE',
                $this->db->escapeIdentifier($dbName),
            ));
        } catch (DatabaseException) {
            
        }

        return parent::dropDatabase($dbName);
    }

    
    protected function _createTableAttributes(array $attributes): string
    {
        return '';
    }

    
    protected function _alterTable(string $alterType, string $table, $processedFields)
    {
        
        if ($alterType === 'DROP') {
            $columnNamesToDrop = $processedFields;

            
            $indexData = $this->db->getIndexData($table);

            foreach ($indexData as $index) {
                if (is_string($columnNamesToDrop)) {
                    $columnNamesToDrop = explode(',', $columnNamesToDrop);
                }

                $fld = array_intersect($columnNamesToDrop, $index->fields);

                
                if ($fld !== []) {
                    $this->_dropIndex($table, $index);
                }
            }

            $fullTable = $this->db->escapeIdentifiers($this->db->schema) . '.' . $this->db->escapeIdentifiers($table);

            
            $fields = implode(',', $this->db->escape((array) $columnNamesToDrop));

            $sql = <<<SQL
                SELECT name
                FROM sys.default_constraints
                WHERE parent_object_id = OBJECT_ID('{$fullTable}')
                AND parent_column_id IN (
                SELECT column_id FROM sys.columns WHERE name IN ({$fields}) AND object_id = OBJECT_ID(N'{$fullTable}')
                )
                SQL;

            foreach ($this->db->query($sql)->getResultArray() as $index) {
                $this->db->query('ALTER TABLE ' . $fullTable . ' DROP CONSTRAINT ' . $index['name'] . '');
            }

            $sql = 'ALTER TABLE ' . $fullTable . ' DROP ';

            $fields = array_map(static fn ($item): string => 'COLUMN [' . trim($item) . ']', (array) $columnNamesToDrop);

            return $sql . implode(',', $fields);
        }

        $sql = 'ALTER TABLE ' . $this->db->escapeIdentifiers($this->db->schema) . '.' . $this->db->escapeIdentifiers($table);
        $sql .= ($alterType === 'ADD') ? 'ADD ' : ' ';

        $sqls = [];

        if ($alterType === 'ADD') {
            foreach ($processedFields as $field) {
                $sqls[] = $sql . ($field['_literal'] !== false ? $field['_literal'] : $this->_processColumn($field));
            }

            return $sqls;
        }

        foreach ($processedFields as $field) {
            if ($field['_literal'] !== false) {
                return false;
            }

            if (isset($field['type'])) {
                $sqls[] = $sql . ' ALTER COLUMN ' . $this->db->escapeIdentifiers($field['name'])
                    . " {$field['type']}{$field['length']}";
            }

            if (! empty($field['default'])) {
                $fullTable = $this->db->escapeIdentifiers($this->db->schema) . '.' . $this->db->escapeIdentifiers($table);
                $colName   = $field['name']; 

                
                $findSql = <<<SQL
                    SELECT dc.name AS constraint_name
                    FROM sys.default_constraints dc
                    JOIN sys.columns c
                        ON dc.parent_object_id = c.object_id
                        AND dc.parent_column_id = c.column_id
                    WHERE dc.parent_object_id = OBJECT_ID(N'{$fullTable}')
                        AND c.name = N'{$colName}';
                    SQL;

                $toDrop = $this->db->query($findSql)->getRowArray();
                if (isset($toDrop['constraint_name']) && $toDrop['constraint_name'] !== '') {
                    $sqls[] = $sql . ' DROP CONSTRAINT ' . $this->db->escapeIdentifiers($toDrop['constraint_name']);
                }

                $sqls[] = $sql . ' ADD CONSTRAINT ' . $this->db->escapeIdentifiers($field['name'] . '_def')
                    . "{$field['default']} FOR " . $this->db->escapeIdentifiers($field['name']);
            }

            $nullable = true; 
            if (isset($field['null']) && ($field['null'] === false || $field['null'] === ' NOT ' . $this->null)) {
                $nullable = false;
            }
            $sqls[] = $sql . ' ALTER COLUMN ' . $this->db->escapeIdentifiers($field['name'])
                . " {$field['type']}{$field['length']} " . ($nullable ? '' : 'NOT') . ' NULL';

            if (! empty($field['comment'])) {
                $sqls[] = 'EXEC sys.sp_addextendedproperty '
                    . "@name=N'Caption', @value=N'" . $field['comment'] . "' , "
                    . "@level0type=N'SCHEMA',@level0name=N'" . $this->db->schema . "', "
                    . "@level1type=N'TABLE',@level1name=N'" . $this->db->escapeIdentifiers($table) . "', "
                    . "@level2type=N'COLUMN',@level2name=N'" . $this->db->escapeIdentifiers($field['name']) . "'";
            }

            if (! empty($field['new_name'])) {
                $sqls[] = "EXEC sp_rename  '[" . $this->db->schema . '].[' . $table . '].[' . $field['name'] . "]' , '" . $field['new_name'] . "', 'COLUMN';";
            }
        }

        return $sqls;
    }

    
    protected function _dropIndex(string $table, object $indexData)
    {
        if ($indexData->type === 'PRIMARY') {
            $sql = 'ALTER TABLE [' . $this->db->schema . '].[' . $table . '] DROP [' . $indexData->name . ']';
        } else {
            $sql = 'DROP INDEX [' . $indexData->name . '] ON [' . $this->db->schema . '].[' . $table . ']';
        }

        return $this->db->simpleQuery($sql);
    }

    
    protected function _processIndexes(string $table, bool $asQuery = false): array
    {
        $sqls = [];

        for ($i = 0, $c = count($this->keys); $i < $c; $i++) {
            for ($i2 = 0, $c2 = count($this->keys[$i]['fields']); $i2 < $c2; $i2++) {
                if (! isset($this->fields[$this->keys[$i]['fields'][$i2]])) {
                    unset($this->keys[$i]['fields'][$i2]);
                }
            }

            if (count($this->keys[$i]['fields']) <= 0) {
                continue;
            }

            $keyName = $this->db->escapeIdentifiers(($this->keys[$i]['keyName'] === '') ?
                $table . '_' . implode('_', $this->keys[$i]['fields']) :
                $this->keys[$i]['keyName']);

            if (in_array($i, $this->uniqueKeys, true)) {
                $sqls[] = 'ALTER TABLE '
                    . $this->db->escapeIdentifiers($this->db->schema) . '.' . $this->db->escapeIdentifiers($table)
                    . ' ADD CONSTRAINT ' . $keyName
                    . ' UNIQUE (' . implode(', ', $this->db->escapeIdentifiers($this->keys[$i]['fields'])) . ');';

                continue;
            }

            $sqls[] = 'CREATE INDEX '
                . $keyName
                . ' ON ' . $this->db->escapeIdentifiers($this->db->schema) . '.' . $this->db->escapeIdentifiers($table)
                . ' (' . implode(', ', $this->db->escapeIdentifiers($this->keys[$i]['fields'])) . ');';
        }

        return $sqls;
    }

    
    protected function _processColumn(array $processedField): string
    {
        return $this->db->escapeIdentifiers($processedField['name'])
            . (empty($processedField['new_name']) ? '' : ' ' . $this->db->escapeIdentifiers($processedField['new_name']))
            . ' ' . $processedField['type'] . ($processedField['type'] === 'text' ? '' : $processedField['length'])
            . $processedField['default']
            . $processedField['null']
            . $processedField['auto_increment']
            . ''
            . $processedField['unique'];
    }

    
    protected function _attributeType(array &$attributes)
    {
        
        if (isset($attributes['CONSTRAINT']) && str_contains(strtolower($attributes['TYPE']), 'int')) {
            $attributes['CONSTRAINT'] = null;
        }

        switch (strtoupper($attributes['TYPE'])) {
            case 'MEDIUMINT':
                $attributes['TYPE']     = 'INTEGER';
                $attributes['UNSIGNED'] = false;
                break;

            case 'INTEGER':
                $attributes['TYPE'] = 'INT';
                break;

            case 'ENUM':
                
                
                
                $maxLength = max(
                    array_map(
                        strlen(...),
                        $attributes['CONSTRAINT'],
                    ),
                );

                $attributes['TYPE']       = 'VARCHAR';
                $attributes['CONSTRAINT'] = $maxLength;
                break;

            case 'TIMESTAMP':
                $attributes['TYPE'] = 'DATETIME';
                break;

            case 'BOOLEAN':
                $attributes['TYPE'] = 'BIT';
                break;

            case 'BLOB':
                $attributes['TYPE'] = 'VARBINARY';
                $attributes['CONSTRAINT'] ??= 'MAX';
                break;

            default:
                break;
        }
    }

    
    protected function _attributeAutoIncrement(array &$attributes, array &$field)
    {
        if (! empty($attributes['AUTO_INCREMENT']) && $attributes['AUTO_INCREMENT'] === true && str_contains(strtolower($field['type']), strtolower('INT'))) {
            $field['auto_increment'] = ' IDENTITY(1,1)';
        }
    }

    
    protected function _dropTable(string $table, bool $ifExists, bool $cascade): string
    {
        $sql = 'DROP TABLE';

        if ($ifExists) {
            $sql .= ' IF EXISTS ';
        }

        $table = ' [' . $this->db->database . '].[' . $this->db->schema . '].[' . $table . '] ';

        $sql .= $table;

        if ($cascade) {
            $sql .= '';
        }

        return $sql;
    }

    
    protected function _dropKeyAsConstraint(string $table, string $constraintName): string
    {
        return "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                WHERE TABLE_NAME= '" . trim($table, '"') . "'
                AND CONSTRAINT_NAME = '" . trim($constraintName, '"') . "'";
    }
}
