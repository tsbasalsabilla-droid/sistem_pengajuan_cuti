<?php

declare(strict_types=1);



namespace CodeIgniter\Database\SQLSRV;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\ResultInterface;
use Config\Feature;


class Builder extends BaseBuilder
{
    
    protected $randomKeyword = [
        'NEWID()',
        'RAND(%d)',
    ];

    
    protected $_quoted_identifier = true;

    
    public $castTextToInt = true;

    
    public $keyPermission = false;

    
    protected function _fromTables(): string
    {
        $from = [];

        foreach ($this->QBFrom as $value) {
            $from[] = str_starts_with($value, '(SELECT') ? $value : $this->getFullName($value);
        }

        return implode(', ', $from);
    }

    
    protected function _truncate(string $table): string
    {
        return 'TRUNCATE TABLE ' . $this->getFullName($table);
    }

    
    public function join(string $table, $cond, string $type = '', ?bool $escape = null)
    {
        if ($type !== '') {
            $type = strtoupper(trim($type));

            if (! in_array($type, $this->joinTypes, true)) {
                $type = '';
            } else {
                $type .= ' ';
            }
        }

        
        
        $this->trackAliases($table);

        if (! is_bool($escape)) {
            $escape = $this->db->protectIdentifiers;
        }

        if (! $this->hasOperator($cond)) {
            $cond = ' USING (' . ($escape ? $this->db->escapeIdentifiers($cond) : $cond) . ')';
        } elseif ($escape === false) {
            $cond = ' ON ' . $cond;
        } else {
            
            if (preg_match_all('/\sAND\s|\sOR\s/i', $cond, $joints, PREG_OFFSET_CAPTURE) >= 1) {
                $conditions = [];
                $joints     = $joints[0];
                array_unshift($joints, ['', 0]);

                for ($i = count($joints) - 1, $pos = strlen($cond); $i >= 0; $i--) {
                    $joints[$i][1] += strlen($joints[$i][0]); 
                    $conditions[$i] = substr($cond, $joints[$i][1], $pos - $joints[$i][1]);
                    $pos            = $joints[$i][1] - strlen($joints[$i][0]);
                    $joints[$i]     = $joints[$i][0];
                }

                ksort($conditions);
            } else {
                $conditions = [$cond];
                $joints     = [''];
            }

            $cond = ' ON ';

            foreach ($conditions as $i => $condition) {
                $operator = $this->getOperator($condition);

                
                if ($operator === false) {
                    $cond .= $joints[$i] . $condition;

                    continue;
                }

                $cond .= $joints[$i];
                $cond .= preg_match('/(\(*)?([\[\]\w\.\'-]+)' . preg_quote($operator, '/') . '(.*)/i', $condition, $match) ? $match[1] . $this->db->protectIdentifiers($match[2]) . $operator . $this->db->protectIdentifiers($match[3]) : $condition;
            }
        }

        
        if ($escape === true) {
            $table = $this->db->protectIdentifiers($table, true, null, false);
        }

        
        $this->QBJoin[] = $type . 'JOIN ' . $this->getFullName($table) . $cond;

        return $this;
    }

    
    protected function _insert(string $table, array $keys, array $unescapedKeys): string
    {
        $fullTableName = $this->getFullName($table);

        
        $statement = 'INSERT INTO ' . $fullTableName . ' (' . implode(',', $keys) . ') VALUES (' . implode(', ', $unescapedKeys) . ')';

        return $this->keyPermission ? $this->addIdentity($fullTableName, $statement) : $statement;
    }

    
    protected function _insertBatch(string $table, array $keys, array $values): string
    {
        $sql = $this->QBOptions['sql'] ?? '';

        
        if ($sql === '') {
            $sql = 'INSERT ' . $this->compileIgnore('insert') . 'INTO ' . $this->getFullName($table)
                . ' (' . implode(', ', $keys) . ")\n{:_table_:}";

            $this->QBOptions['sql'] = $sql;
        }

        if (isset($this->QBOptions['setQueryAsData'])) {
            $data = $this->QBOptions['setQueryAsData'];
        } else {
            $data = 'VALUES ' . implode(', ', $this->formatValues($values));
        }

        return str_replace('{:_table_:}', $data, $sql);
    }

    
    protected function _update(string $table, array $values): string
    {
        $valstr = [];

        foreach ($values as $key => $val) {
            $valstr[] = $key . ' = ' . $val;
        }

        $fullTableName = $this->getFullName($table);

        $statement = sprintf('UPDATE %s%s SET ', empty($this->QBLimit) ? '' : 'TOP(' . $this->QBLimit . ') ', $fullTableName);

        $statement .= implode(', ', $valstr)
            . $this->compileWhereHaving('QBWhere')
            . $this->compileOrderBy();

        return $this->keyPermission ? $this->addIdentity($fullTableName, $statement) : $statement;
    }

    
    public function increment(string $column, int $value = 1)
    {
        $column = $this->db->protectIdentifiers($column);

        if ($this->castTextToInt) {
            $values = [$column => "CONVERT(VARCHAR(MAX),CONVERT(INT,CONVERT(VARCHAR(MAX), {$column})) + {$value})"];
        } else {
            $values = [$column => "{$column} + {$value}"];
        }

        $sql = $this->_update($this->QBFrom[0], $values);

        if (! $this->testMode) {
            $this->resetWrite();

            return $this->db->query($sql, $this->binds, false);
        }

        return true;
    }

    
    public function decrement(string $column, int $value = 1)
    {
        $column = $this->db->protectIdentifiers($column);

        if ($this->castTextToInt) {
            $values = [$column => "CONVERT(VARCHAR(MAX),CONVERT(INT,CONVERT(VARCHAR(MAX), {$column})) - {$value})"];
        } else {
            $values = [$column => "{$column} + {$value}"];
        }

        $sql = $this->_update($this->QBFrom[0], $values);

        if (! $this->testMode) {
            $this->resetWrite();

            return $this->db->query($sql, $this->binds, false);
        }

        return true;
    }

    
    private function getFullName(string $table): string
    {
        $alias = '';

        if (str_contains($table, ' ')) {
            $alias = explode(' ', $table);
            $table = array_shift($alias);
            $alias = ' ' . implode(' ', $alias);
        }

        if ($this->db->escapeChar === '"') {
            if (str_contains($table, '.') && ! str_starts_with($table, '.') && ! str_ends_with($table, '.')) {
                $dbInfo   = explode('.', $table);
                $database = $this->db->getDatabase();
                $table    = $dbInfo[0];

                if (count($dbInfo) === 3) {
                    $database  = str_replace('"', '', $dbInfo[0]);
                    $schema    = str_replace('"', '', $dbInfo[1]);
                    $tableName = str_replace('"', '', $dbInfo[2]);
                } else {
                    $schema    = str_replace('"', '', $dbInfo[0]);
                    $tableName = str_replace('"', '', $dbInfo[1]);
                }

                return '"' . $database . '"."' . $schema . '"."' . str_replace('"', '', $tableName) . '"' . $alias;
            }

            return '"' . $this->db->getDatabase() . '"."' . $this->db->schema . '"."' . str_replace('"', '', $table) . '"' . $alias;
        }

        return '[' . $this->db->getDatabase() . '].[' . $this->db->schema . '].[' . str_replace('"', '', $table) . ']' . str_replace('"', '', $alias);
    }

    
    private function addIdentity(string $fullTable, string $insert): string
    {
        return 'SET IDENTITY_INSERT ' . $fullTable . " ON\n" . $insert . "\nSET IDENTITY_INSERT " . $fullTable . ' OFF';
    }

    
    protected function _limit(string $sql, bool $offsetIgnore = false): string
    {
        
        
        
        
        $limitZeroAsAll = config(Feature::class)->limitZeroAsAll ?? true;
        if (! $limitZeroAsAll && $this->QBLimit === 0) {
            return "SELECT * \nFROM " . $this->_fromTables() . ' WHERE 1=0 ';
        }

        if (empty($this->QBOrderBy)) {
            $sql .= ' ORDER BY (SELECT NULL) ';
        }

        if ($offsetIgnore) {
            $sql .= ' OFFSET 0 ';
        } else {
            $sql .= is_int($this->QBOffset) ? ' OFFSET ' . $this->QBOffset : ' OFFSET 0 ';
        }

        return $sql . ' ROWS FETCH NEXT ' . $this->QBLimit . ' ROWS ONLY ';
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

        $sql = $this->_replace($table, array_keys($this->QBSet), array_values($this->QBSet));

        $this->resetWrite();

        if ($this->testMode) {
            return $sql;
        }

        $this->db->simpleQuery('SET IDENTITY_INSERT ' . $this->getFullName($table) . ' ON');

        $result = $this->db->query($sql, $this->binds, false);
        $this->db->simpleQuery('SET IDENTITY_INSERT ' . $this->getFullName($table) . ' OFF');

        return $result;
    }

    
    protected function _replace(string $table, array $keys, array $values): string
    {
        
        
        $pKeys     = $this->db->getIndexData($table);
        $keyFields = [];

        foreach ($pKeys as $key) {
            if ($key->type === 'PRIMARY') {
                $keyFields = array_merge($keyFields, $key->fields);
            }

            if ($key->type === 'UNIQUE') {
                $keyFields = array_merge($keyFields, $key->fields);
            }
        }

        
        $escKeyFields = array_map(fn (string $field): string => $this->db->protectIdentifiers($field), array_values(array_unique($keyFields)));

        
        $binds = $this->binds;
        array_walk($binds, static function (&$item): void {
            $item = $item[0];
        });

        
        $common = array_intersect($keys, $escKeyFields);
        $bingo  = [];

        foreach ($common as $v) {
            $k = array_search($v, $keys, true);

            $bingo[$keys[$k]] = $binds[trim($values[$k], ':')];
        }

        
        $builder = $this->db->table($table);

        foreach ($bingo as $k => $v) {
            $builder->where($k, $v);
        }

        $q = $builder->get()->getResult();

        
        if ($q !== []) {
            $delete = $this->db->table($table);

            foreach ($bingo as $k => $v) {
                $delete->where($k, $v);
            }

            $delete->delete();
        }

        return sprintf('INSERT INTO %s (%s) VALUES (%s);', $this->getFullName($table), implode(',', $keys), implode(',', $values));
    }

    
    protected function maxMinAvgSum(string $select = '', string $alias = '', string $type = 'MAX')
    {
        
        if ($type !== 'AVG') {
            return parent::maxMinAvgSum($select, $alias, $type);
        }

        if ($select === '') {
            throw DataException::forEmptyInputGiven('Select');
        }

        if (str_contains($select, ',')) {
            throw DataException::forInvalidArgument('Column name not separated by comma');
        }

        if ($alias === '') {
            $alias = $this->createAliasFromTable(trim($select));
        }

        $sql = $type . '( CAST( ' . $this->db->protectIdentifiers(trim($select)) . ' AS FLOAT ) ) AS ' . $this->db->escapeIdentifiers(trim($alias));

        $this->QBSelect[]   = $sql;
        $this->QBNoEscape[] = null;

        return $this;
    }

    
    public function countAll(bool $reset = true)
    {
        $table = $this->QBFrom[0];

        $sql = $this->countString . $this->db->escapeIdentifiers('numrows') . ' FROM ' . $this->getFullName($table);

        if ($this->testMode) {
            return $sql;
        }

        $query = $this->db->query($sql, null, false);
        if (empty($query->getResult())) {
            return 0;
        }

        $query = $query->getRow();

        if ($reset) {
            $this->resetSelect();
        }

        return (int) $query->numrows;
    }

    
    protected function _delete(string $table): string
    {
        return 'DELETE' . (empty($this->QBLimit) ? '' : ' TOP (' . $this->QBLimit . ') ') . ' FROM ' . $this->getFullName($table) . $this->compileWhereHaving('QBWhere');
    }

    
    public function delete($where = '', ?int $limit = null, bool $resetData = true)
    {
        $table = $this->db->protectIdentifiers($this->QBFrom[0], true, null, false);

        if ($where !== '') {
            $this->where($where);
        }

        if ($this->QBWhere === []) {
            if ($this->db->DBDebug) {
                throw new DatabaseException('Deletes are not allowed unless they contain a "where" or "like" clause.');
            }

            return false; 
        }

        if ($limit !== null && $limit !== 0) {
            $this->QBLimit = $limit;
        }

        $sql = $this->_delete($table);

        if ($resetData) {
            $this->resetWrite();
        }

        return $this->testMode ? $sql : $this->db->query($sql, $this->binds, false);
    }

    
    protected function compileSelect($selectOverride = false): string
    {
        
        if ($selectOverride !== false) {
            $sql = $selectOverride;
        } else {
            $sql = $this->QBDistinct ? 'SELECT DISTINCT ' : 'SELECT ';

            
            if (empty($this->QBSelect) && $this->QBGroupBy !== [] && is_array($this->QBGroupBy)) {
                foreach ($this->QBGroupBy as $field) {
                    $this->QBSelect[] = is_array($field) ? $field['field'] : $field;
                }
            }

            if (empty($this->QBSelect)) {
                $sql .= '*';
            } else {
                
                
                
                foreach ($this->QBSelect as $key => $val) {
                    $noEscape             = $this->QBNoEscape[$key] ?? null;
                    $this->QBSelect[$key] = $this->db->protectIdentifiers($val, false, $noEscape);
                }

                $sql .= implode(', ', $this->QBSelect);
            }
        }

        
        if ($this->QBFrom !== []) {
            $sql .= "\nFROM " . $this->_fromTables();
        }

        
        if (! empty($this->QBJoin)) {
            $sql .= "\n" . implode("\n", $this->QBJoin);
        }

        $sql .= $this->compileWhereHaving('QBWhere')
            . $this->compileGroupBy()
            . $this->compileWhereHaving('QBHaving')
            . $this->compileOrderBy(); 

        
        $limitZeroAsAll = config(Feature::class)->limitZeroAsAll ?? true;
        if ($limitZeroAsAll) {
            if ($this->QBLimit) {
                $sql = $this->_limit($sql . "\n");
            }
        } elseif ($this->QBLimit !== false || $this->QBOffset) {
            $sql = $this->_limit($sql . "\n");
        }

        return $this->unionInjection($sql);
    }

    
    public function get(?int $limit = null, int $offset = 0, bool $reset = true)
    {
        $limitZeroAsAll = config(Feature::class)->limitZeroAsAll ?? true;
        if ($limitZeroAsAll && $limit === 0) {
            $limit = null;
        }

        if ($limit !== null) {
            $this->limit($limit, $offset);
        }

        $result = $this->testMode ? $this->getCompiledSelect($reset) : $this->db->query($this->compileSelect(), $this->binds, false);

        if ($reset) {
            $this->resetSelect();

            
            $this->binds = [];
        }

        return $result;
    }

    
    protected function _upsertBatch(string $table, array $keys, array $values): string
    {
        $sql = $this->QBOptions['sql'] ?? '';

        
        if ($sql === '') {
            $fullTableName = $this->getFullName($table);

            $constraints = $this->QBOptions['constraints'] ?? [];

            $tableIdentity = $this->QBOptions['tableIdentity'] ?? '';
            $sql           = "SELECT name from syscolumns where id = Object_ID('" . $table . "') and colstat = 1";
            if (($query = $this->db->query($sql)) === false) {
                throw new DatabaseException('Failed to get table identity');
            }
            $query = $query->getResultObject();

            foreach ($query as $row) {
                $tableIdentity = '"' . $row->name . '"';
            }
            $this->QBOptions['tableIdentity'] = $tableIdentity;

            $identityInFields = in_array($tableIdentity, $keys, true);

            $fieldNames = array_map(static fn ($columnName): string => trim($columnName, '"'), $keys);

            if (empty($constraints)) {
                $tableIndexes = $this->db->getIndexData($table);

                $uniqueIndexes = array_filter($tableIndexes, static function ($index) use ($fieldNames): bool {
                    $hasAllFields = count(array_intersect($index->fields, $fieldNames)) === count($index->fields);

                    return $index->type === 'PRIMARY' && $hasAllFields;
                });

                
                if ($uniqueIndexes === []) {
                    $uniqueIndexes = array_filter($tableIndexes, static function ($index) use ($fieldNames): bool {
                        $hasAllFields = count(array_intersect($index->fields, $fieldNames)) === count($index->fields);

                        return $index->type === 'UNIQUE' && $hasAllFields;
                    });
                }

                
                foreach ($uniqueIndexes as $index) {
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

            $alias = $this->QBOptions['alias'] ?? '"_upsert"';

            $updateFields = $this->QBOptions['updateFields'] ?? $this->updateFields($keys, false, $constraints)->QBOptions['updateFields'] ?? [];

            $sql = 'MERGE INTO ' . $fullTableName . "\nUSING (\n";

            $sql .= '{:_table_:}';

            $sql .= ") {$alias} (";

            $sql .= implode(', ', $keys);

            $sql .= ')';

            $sql .= "\nON (";

            $sql .= implode(
                ' AND ',
                array_map(
                    static fn ($key, $value) => (
                        ($value instanceof RawSql && is_string($key))
                        ?
                        $fullTableName . '.' . $key . ' = ' . $value
                        :
                        (
                            $value instanceof RawSql
                            ?
                            $value
                            :
                            $fullTableName . '.' . $value . ' = ' . $alias . '.' . $value
                        )
                    ),
                    array_keys($constraints),
                    $constraints,
                ),
            ) . ")\n";

            $sql .= "WHEN MATCHED THEN UPDATE SET\n";

            $sql .= implode(
                ",\n",
                array_map(
                    static fn ($key, $value): string => $key . ($value instanceof RawSql ?
                        ' = ' . $value :
                    " = {$alias}.{$value}"),
                    array_keys($updateFields),
                    $updateFields,
                ),
            );

            $sql .= "\nWHEN NOT MATCHED THEN INSERT (" . implode(', ', $keys) . ")\nVALUES ";

            $sql .= (
                '(' . implode(
                    ', ',
                    array_map(
                        static fn ($columnName): string => $columnName === $tableIdentity
                    ? "CASE WHEN {$alias}.{$columnName} IS NULL THEN (SELECT "
                    . 'isnull(IDENT_CURRENT(\'' . $fullTableName . '\')+IDENT_INCR(\''
                    . $fullTableName . "'),1)) ELSE {$alias}.{$columnName} END"
                    : "{$alias}.{$columnName}",
                        $keys,
                    ),
                ) . ');'
            );

            $sql = $identityInFields ? $this->addIdentity($fullTableName, $sql) : $sql;

            $this->QBOptions['sql'] = $sql;
        }

        if (isset($this->QBOptions['setQueryAsData'])) {
            $data = $this->QBOptions['setQueryAsData'];
        } else {
            $data = 'VALUES ' . implode(', ', $this->formatValues($values)) . "\n";
        }

        return str_replace('{:_table_:}', $data, $sql);
    }

    
    protected function fieldsFromQuery(string $sql): array
    {
        return $this->db->query('SELECT TOP 1 * FROM (' . $sql . ') _u_')->getFieldNames();
    }
}
