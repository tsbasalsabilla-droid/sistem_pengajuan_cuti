<?php

declare(strict_types=1);



namespace CodeIgniter\Database\SQLite3;

use CodeIgniter\Database\Exceptions\DataException;
use stdClass;


class Table
{
    
    protected $fields = [];

    
    protected $keys = [];

    
    protected $foreignKeys = [];

    
    protected $tableName;

    
    protected $prefixedTableName;

    
    protected $db;

    
    protected $forge;

    
    public function __construct(Connection $db, Forge $forge)
    {
        $this->db    = $db;
        $this->forge = $forge;
    }

    
    public function fromTable(string $table)
    {
        $this->prefixedTableName = $table;

        $prefix = $this->db->DBPrefix;

        if (! empty($prefix) && str_starts_with($table, $prefix)) {
            $table = substr($table, strlen($prefix));
        }

        if (! $this->db->tableExists($this->prefixedTableName)) {
            throw DataException::forTableNotFound($this->prefixedTableName);
        }

        $this->tableName = $table;

        $this->fields = $this->formatFields($this->db->getFieldData($table));

        $this->keys = array_merge($this->keys, $this->formatKeys($this->db->getIndexData($table)));

        
        $primaryIndexes = array_filter($this->keys, static fn ($index): bool => $index['type'] === 'primary');

        if ($primaryIndexes !== [] && count($primaryIndexes) > 1 && array_key_exists('primary', $this->keys)) {
            unset($this->keys['primary']);
        }

        $this->foreignKeys = $this->db->getForeignKeyData($table);

        return $this;
    }

    
    public function run(): bool
    {
        $this->db->query('PRAGMA foreign_keys = OFF');

        $this->db->transStart();

        $this->forge->renameTable($this->tableName, "temp_{$this->tableName}");

        $this->forge->reset();

        $this->createTable();

        $this->copyData();

        $this->forge->dropTable("temp_{$this->tableName}");

        $success = $this->db->transComplete();

        $this->db->query('PRAGMA foreign_keys = ON');

        $this->db->resetDataCache();

        return $success;
    }

    
    public function dropColumn($columns)
    {
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        }

        foreach ($columns as $column) {
            $column = trim($column);
            if (isset($this->fields[$column])) {
                unset($this->fields[$column]);
            }
        }

        return $this;
    }

    
    public function modifyColumn(array $fieldsToModify)
    {
        foreach ($fieldsToModify as $field) {
            $oldName = $field['name'];
            unset($field['name']);

            $this->fields[$oldName] = $field;
        }

        return $this;
    }

    
    public function dropPrimaryKey(): Table
    {
        $primaryIndexes = array_filter($this->keys, static fn ($index): bool => strtolower($index['type']) === 'primary');

        foreach (array_keys($primaryIndexes) as $key) {
            unset($this->keys[$key]);
        }

        return $this;
    }

    
    public function dropForeignKey(string $foreignName)
    {
        if (empty($this->foreignKeys)) {
            return $this;
        }

        if (isset($this->foreignKeys[$foreignName])) {
            unset($this->foreignKeys[$foreignName]);
        }

        return $this;
    }

    
    public function addPrimaryKey(array $fields): Table
    {
        $primaryIndexes = array_filter($this->keys, static fn ($index): bool => strtolower($index['type']) === 'primary');

        
        if ($primaryIndexes !== []) {
            return $this;
        }

        
        $pk = [
            'fields' => $fields['fields'],
            'type'   => 'primary',
        ];

        $this->keys['primary'] = $pk;

        return $this;
    }

    
    public function addForeignKey(array $foreignKeys)
    {
        $fk = [];

        
        foreach ($foreignKeys as $row) {
            $obj                      = new stdClass();
            $obj->column_name         = $row['field'];
            $obj->foreign_table_name  = $row['referenceTable'];
            $obj->foreign_column_name = $row['referenceField'];
            $obj->on_delete           = $row['onDelete'];
            $obj->on_update           = $row['onUpdate'];

            $fk[] = $obj;
        }

        $this->foreignKeys = array_merge($this->foreignKeys, $fk);

        return $this;
    }

    
    protected function createTable()
    {
        $this->dropIndexes();
        $this->db->resetDataCache();

        
        $fields = [];

        foreach ($this->fields as $name => $field) {
            if (isset($field['new_name'])) {
                $fields[$field['new_name']] = $field;

                continue;
            }

            $fields[$name] = $field;
        }

        $this->forge->addField($fields);

        $fieldNames = array_keys($fields);

        $this->keys = array_filter(
            $this->keys,
            static fn ($index): bool => count(array_intersect($index['fields'], $fieldNames)) === count($index['fields']),
        );

        
        if (is_array($this->keys)) {
            foreach ($this->keys as $keyName => $key) {
                switch ($key['type']) {
                    case 'primary':
                        $this->forge->addPrimaryKey($key['fields']);
                        break;

                    case 'unique':
                        $this->forge->addUniqueKey($key['fields'], $keyName);
                        break;

                    case 'index':
                        $this->forge->addKey($key['fields'], false, false, $keyName);
                        break;
                }
            }
        }

        foreach ($this->foreignKeys as $foreignKey) {
            $this->forge->addForeignKey(
                $foreignKey->column_name,
                trim($foreignKey->foreign_table_name, $this->db->DBPrefix),
                $foreignKey->foreign_column_name,
            );
        }

        return $this->forge->createTable($this->tableName);
    }

    
    protected function copyData()
    {
        $exFields  = [];
        $newFields = [];

        foreach ($this->fields as $name => $details) {
            $newFields[] = $details['new_name'] ?? $name;
            $exFields[]  = $name;
        }

        $exFields = implode(
            ', ',
            array_map(fn ($item) => $this->db->protectIdentifiers($item), $exFields),
        );
        $newFields = implode(
            ', ',
            array_map(fn ($item) => $this->db->protectIdentifiers($item), $newFields),
        );

        $this->db->query(
            "INSERT INTO {$this->prefixedTableName}({$newFields}) SELECT {$exFields} FROM {$this->db->DBPrefix}temp_{$this->tableName}",
        );
    }

    
    protected function formatFields($fields)
    {
        if (! is_array($fields)) {
            return $fields;
        }

        $return = [];

        foreach ($fields as $field) {
            $return[$field->name] = [
                'type'    => $field->type,
                'default' => $field->default,
                'null'    => $field->nullable,
            ];

            if ($field->default === null) {
                
                unset($return[$field->name]['default']);
            } elseif ($field->default === 'NULL') {
                
                $return[$field->name]['default'] = null;
            } else {
                $default = trim($field->default, "'");

                if ($this->isIntegerType($field->type)) {
                    $default = (int) $default;
                } elseif ($this->isNumericType($field->type)) {
                    $default = (float) $default;
                }

                $return[$field->name]['default'] = $default;
            }

            if ($field->primary_key) {
                $this->keys['primary'] = [
                    'fields' => [$field->name],
                    'type'   => 'primary',
                ];
            }
        }

        return $return;
    }

    
    private function isIntegerType(string $type): bool
    {
        return str_contains(strtoupper($type), 'INT');
    }

    
    private function isNumericType(string $type): bool
    {
        return in_array(strtoupper($type), ['NUMERIC', 'DECIMAL'], true);
    }

    
    protected function formatKeys($keys)
    {
        $return = [];

        foreach ($keys as $name => $key) {
            $return[strtolower($name)] = [
                'fields' => $key->fields,
                'type'   => strtolower($key->type),
            ];
        }

        return $return;
    }

    
    protected function dropIndexes()
    {
        if (! is_array($this->keys) || $this->keys === []) {
            return;
        }

        foreach (array_keys($this->keys) as $name) {
            if ($name === 'primary') {
                continue;
            }

            $this->db->query("DROP INDEX IF EXISTS '{$name}'");
        }
    }
}
