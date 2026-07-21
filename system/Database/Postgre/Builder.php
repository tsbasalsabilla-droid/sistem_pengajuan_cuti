<?php

declare(strict_types=1);



namespace CodeIgniter\Database\Postgre;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Exceptions\InvalidArgumentException;


class Builder extends BaseBuilder
{
    
    protected $randomKeyword = [
        'RANDOM()',
    ];

    
    protected $supportedIgnoreStatements = [
        'insert' => 'ON CONFLICT DO NOTHING',
    ];

    
    protected function compileIgnore(string $statement)
    {
        $sql = parent::compileIgnore($statement);

        if (! empty($sql)) {
            $sql = ' ' . trim($sql);
        }

        return $sql;
    }

    
    public function orderBy(string $orderBy, string $direction = '', ?bool $escape = null)
    {
        $direction = strtoupper(trim($direction));
        if ($direction === 'RANDOM') {
            if (ctype_digit($orderBy)) {
                $orderBy = (float) ($orderBy > 1 ? "0.{$orderBy}" : $orderBy);
            }

            if (is_float($orderBy)) {
                $this->db->simpleQuery("SET SEED {$orderBy}");
            }

            $orderBy   = $this->randomKeyword[0];
            $direction = '';
            $escape    = false;
        }

        return parent::orderBy($orderBy, $direction, $escape);
    }

    
    public function increment(string $column, int $value = 1)
    {
        $column = $this->db->protectIdentifiers($column);

        $sql = $this->_update($this->QBFrom[0], [$column => "to_number({$column}, '9999999') + {$value}"]);

        if (! $this->testMode) {
            $this->resetWrite();

            return $this->db->query($sql, $this->binds, false);
        }

        return true;
    }

    
    public function decrement(string $column, int $value = 1)
    {
        $column = $this->db->protectIdentifiers($column);

        $sql = $this->_update($this->QBFrom[0], [$column => "to_number({$column}, '9999999') - {$value}"]);

        if (! $this->testMode) {
            $this->resetWrite();

            return $this->db->query($sql, $this->binds, false);
        }

        return true;
    }

    
    public function replace(?array $set = null)
    {
        if ($set !== null) {
            $this->set($set);
        }

        if ($this->QBSet === []) {
            if ($this->db->DBDebug) {
                throw new DatabaseException('You must use the "set" method to update an entry.');
            }

            return false; 
        }

        $table = $this->QBFrom[0];
        $set   = $this->binds;

        array_walk($set, static function (array &$item): void {
            $item = $item[0];
        });

        $key   = array_key_first($set);
        $value = $set[$key];

        $builder = $this->db->table($table);
        $exists  = $builder->where($key, $value, true)->get()->getFirstRow();

        if (empty($exists) && $this->testMode) {
            $result = $this->getCompiledInsert();
        } elseif (empty($exists)) {
            $result = $builder->insert($set);
        } elseif ($this->testMode) {
            $result = $this->where($key, $value, true)->getCompiledUpdate();
        } else {
            array_shift($set);
            $result = $builder->where($key, $value, true)->update($set);
        }

        unset($builder);
        $this->resetWrite();
        $this->binds = [];

        return $result;
    }

    
    protected function _insert(string $table, array $keys, array $unescapedKeys): string
    {
        return trim(sprintf('INSERT INTO %s (%s) VALUES (%s) %s', $table, implode(', ', $keys), implode(', ', $unescapedKeys), $this->compileIgnore('insert')));
    }

    
    protected function _insertBatch(string $table, array $keys, array $values): string
    {
        $sql = $this->QBOptions['sql'] ?? '';

        
        if ($sql === '') {
            $sql = 'INSERT INTO ' . $table . '(' . implode(', ', $keys) . ")\n{:_table_:}\n";

            $sql .= $this->compileIgnore('insert');

            $this->QBOptions['sql'] = $sql;
        }

        if (isset($this->QBOptions['setQueryAsData'])) {
            $data = $this->QBOptions['setQueryAsData'];
        } else {
            $data = 'VALUES ' . implode(', ', $this->formatValues($values));
        }

        return str_replace('{:_table_:}', $data, $sql);
    }

    
    public function delete($where = '', ?int $limit = null, bool $resetData = true)
    {
        if ($limit !== null && $limit !== 0 || ! empty($this->QBLimit)) {
            throw new DatabaseException('PostgreSQL does not allow LIMITs on DELETE queries.');
        }

        return parent::delete($where, $limit, $resetData);
    }

    
    protected function _limit(string $sql, bool $offsetIgnore = false): string
    {
        return $sql . ' LIMIT ' . $this->QBLimit . ($this->QBOffset ? " OFFSET {$this->QBOffset}" : '');
    }

    
    protected function _update(string $table, array $values): string
    {
        if (! empty($this->QBLimit)) {
            throw new DatabaseException('Postgres does not support LIMITs with UPDATE queries.');
        }

        $this->QBOrderBy = [];

        return parent::_update($table, $values);
    }

    
    protected function _delete(string $table): string
    {
        $this->QBLimit = false;

        return parent::_delete($table);
    }

    
    protected function _truncate(string $table): string
    {
        return 'TRUNCATE ' . $table . ' RESTART IDENTITY';
    }

    
    protected function _like_statement(?string $prefix, string $column, ?string $not, string $bind, bool $insensitiveSearch = false): string
    {
        $op = $insensitiveSearch ? 'ILIKE' : 'LIKE';

        return "{$prefix} {$column} {$not} {$op} :{$bind}:";
    }

    
    public function join(string $table, $cond, string $type = '', ?bool $escape = null)
    {
        if (! in_array('FULL OUTER', $this->joinTypes, true)) {
            $this->joinTypes = array_merge($this->joinTypes, ['FULL OUTER']);
        }

        return parent::join($table, $cond, $type, $escape);
    }

    
    protected function _updateBatch(string $table, array $keys, array $values): string
    {
        $sql = $this->QBOptions['sql'] ?? '';

        
        if ($sql === '') {
            $constraints = $this->QBOptions['constraints'] ?? [];

            if ($constraints === []) {
                if ($this->db->DBDebug) {
                    throw new DatabaseException('You must specify a constraint to match on for batch updates.'); 
                }

                return ''; 
            }

            $updateFields = $this->QBOptions['updateFields'] ??
                $this->updateFields($keys, false, $constraints)->QBOptions['updateFields'] ??
                [];

            $alias = $this->QBOptions['alias'] ?? '_u';

            $sql = 'UPDATE ' . $this->compileIgnore('update') . $table . "\n";

            $sql .= "SET\n";

            $that = $this;
            $sql .= implode(
                ",\n",
                array_map(
                    static fn ($key, $value): string => $key . ($value instanceof RawSql ?
                            ' = ' . $value :
                            ' = ' . $that->cast($alias . '.' . $value, $that->getFieldType($table, $key))),
                    array_keys($updateFields),
                    $updateFields,
                ),
            ) . "\n";

            $sql .= "FROM (\n{:_table_:}";

            $sql .= ') ' . $alias . "\n";

            $sql .= 'WHERE ' . implode(
                ' AND ',
                array_map(
                    static function ($key, $value) use ($table, $alias, $that): string|RawSql {
                        if ($value instanceof RawSql && is_string($key)) {
                            return $table . '.' . $key . ' = ' . $value;
                        }

                        if ($value instanceof RawSql) {
                            return $value;
                        }

                        return $table . '.' . $value . ' = '
                            . $that->cast($alias . '.' . $value, $that->getFieldType($table, $value));
                    },
                    array_keys($constraints),
                    $constraints,
                ),
            );

            $this->QBOptions['sql'] = $sql;
        }

        if (isset($this->QBOptions['setQueryAsData'])) {
            $data = $this->QBOptions['setQueryAsData'];
        } else {
            $data = implode(
                " UNION ALL\n",
                array_map(
                    static fn ($value): string => 'SELECT ' . implode(', ', array_map(
                        static fn ($key, $index): string => $index . ' ' . $key,
                        $keys,
                        $value,
                    )),
                    $values,
                ),
            ) . "\n";
        }

        return str_replace('{:_table_:}', $data, $sql);
    }

    
    private function cast(string $expression, ?string $type): string
    {
        return ($type === null) ? $expression : 'CAST(' . $expression . ' AS ' . strtoupper($type) . ')';
    }

    
    private function getFieldType(string $table, string $fieldName): ?string
    {
        $fieldName = trim($fieldName, $this->db->escapeChar);

        if (! isset($this->QBOptions['fieldTypes'][$table])) {
            $this->QBOptions['fieldTypes'][$table] = [];

            foreach ($this->db->getFieldData($table) as $field) {
                $type = $field->type;

                
                
                
                if ($field->type === 'character') {
                    $type = $field->type . '(' . $field->max_length . ')';
                }

                $this->QBOptions['fieldTypes'][$table][$field->name] = $type;
            }
        }

        return $this->QBOptions['fieldTypes'][$table][$fieldName] ?? null;
    }

    
    protected function _upsertBatch(string $table, array $keys, array $values): string
    {
        $sql = $this->QBOptions['sql'] ?? '';

        
        if ($sql === '') {
            $fieldNames = array_map(static fn ($columnName): string => trim($columnName, '"'), $keys);

            $constraints = $this->QBOptions['constraints'] ?? [];

            if (empty($constraints)) {
                $allIndexes = array_filter($this->db->getIndexData($table), static function ($index) use ($fieldNames): bool {
                    $hasAllFields = count(array_intersect($index->fields, $fieldNames)) === count($index->fields);

                    return ($index->type === 'UNIQUE' || $index->type === 'PRIMARY') && $hasAllFields;
                });

                foreach ($allIndexes as $index) {
                    $constraints = $index->fields;
                    break;
                }

                $constraints = $this->onConstraint($constraints)->QBOptions['constraints'] ?? [];
            }

            if (empty($constraints)) {
                if ($this->db->DBDebug) {
                    throw new DatabaseException('No constraint found for upsert.');
                }

                return ''; 
            }

            
            
            
            foreach ($constraints as $constraint) {
                $key = array_search(trim((string) $constraint, '"'), $fieldNames, true);

                if ($key !== false) {
                    foreach ($values as $arrayKey => $value) {
                        if (strtoupper((string) $value[$key]) === 'NULL') {
                            $values[$arrayKey][$key] = 'DEFAULT';
                        }
                    }
                }
            }

            $alias = $this->QBOptions['alias'] ?? '"excluded"';

            if (strtolower($alias) !== '"excluded"') {
                throw new InvalidArgumentException('Postgres alias is always named "excluded". A custom alias cannot be used.');
            }

            $updateFields = $this->QBOptions['updateFields'] ?? $this->updateFields($keys, false, $constraints)->QBOptions['updateFields'] ?? [];

            $sql = 'INSERT INTO ' . $table . ' (';

            $sql .= implode(', ', $keys);

            $sql .= ")\n";

            $sql .= '{:_table_:}';

            $sql .= 'ON CONFLICT(' . implode(',', $constraints) . ")\n";

            $sql .= "DO UPDATE SET\n";

            $sql .= implode(
                ",\n",
                array_map(
                    static fn ($key, $value): string => $key . ($value instanceof RawSql ?
                    " = {$value}" :
                    " = {$alias}.{$value}"),
                    array_keys($updateFields),
                    $updateFields,
                ),
            );

            $this->QBOptions['sql'] = $sql;
        }

        if (isset($this->QBOptions['setQueryAsData'])) {
            $data = $this->QBOptions['setQueryAsData'];
        } else {
            $data = 'VALUES ' . implode(', ', $this->formatValues($values)) . "\n";
        }

        return str_replace('{:_table_:}', $data, $sql);
    }

    
    protected function _deleteBatch(string $table, array $keys, array $values): string
    {
        $sql = $this->QBOptions['sql'] ?? '';

        
        if ($sql === '') {
            $constraints = $this->QBOptions['constraints'] ?? [];

            if ($constraints === []) {
                if ($this->db->DBDebug) {
                    throw new DatabaseException('You must specify a constraint to match on for batch deletes.'); 
                }

                return ''; 
            }

            $alias = $this->QBOptions['alias'] ?? '_u';

            $sql = 'DELETE FROM ' . $table . "\n";

            $sql .= "USING (\n{:_table_:}";

            $sql .= ') ' . $alias . "\n";

            $that = $this;
            $sql .= 'WHERE ' . implode(
                ' AND ',
                array_map(
                    static function ($key, $value) use ($table, $alias, $that): RawSql|string {
                        if ($value instanceof RawSql) {
                            return $value;
                        }

                        if (is_string($key)) {
                            return $table . '.' . $key . ' = '
                                . $that->cast(
                                    $alias . '.' . $value,
                                    $that->getFieldType($table, $key),
                                );
                        }

                        return $table . '.' . $value . ' = ' . $alias . '.' . $value;
                    },
                    array_keys($constraints),
                    $constraints,
                ),
            );

            
            foreach ($this->QBWhere as $key => $where) {
                foreach ($this->binds as $field => $bind) {
                    $this->QBWhere[$key]['condition'] = str_replace(':' . $field . ':', $bind[0], $where['condition']);
                }
            }

            $sql .= ' ' . str_replace(
                'WHERE ',
                'AND ',
                $this->compileWhereHaving('QBWhere'),
            );

            $this->QBOptions['sql'] = $sql;
        }

        if (isset($this->QBOptions['setQueryAsData'])) {
            $data = $this->QBOptions['setQueryAsData'];
        } else {
            $data = implode(
                " UNION ALL\n",
                array_map(
                    static fn ($value): string => 'SELECT ' . implode(', ', array_map(
                        static fn ($key, $index): string => $index . ' ' . $key,
                        $keys,
                        $value,
                    )),
                    $values,
                ),
            ) . "\n";
        }

        return str_replace('{:_table_:}', $data, $sql);
    }
}
