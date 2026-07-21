<?php

declare(strict_types=1);



namespace CodeIgniter;

use Closure;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Exceptions\BadMethodCallException;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Exceptions\ModelException;
use CodeIgniter\Validation\ValidationInterface;
use Config\Database;
use Config\Feature;
use stdClass;


class Model extends BaseModel
{
    
    protected $table;

    
    protected $primaryKey = 'id';

    
    protected $useAutoIncrement = true;

    
    protected $builder;

    
    protected $tempData = [];

    
    protected $escape = [];

    
    private array $builderMethodsNotAvailable = [
        'getCompiledInsert',
        'getCompiledSelect',
        'getCompiledUpdate',
    ];

    public function __construct(?ConnectionInterface $db = null, ?ValidationInterface $validation = null)
    {
        
        $db ??= Database::connect($this->DBGroup);

        $this->db = $db;

        parent::__construct($validation);
    }

    
    public function setTable(string $table)
    {
        $this->table = $table;

        return $this;
    }

    protected function doFind(bool $singleton, $id = null)
    {
        $builder = $this->builder();
        $useCast = $this->useCasts();

        if ($useCast) {
            $returnType = $this->tempReturnType;
            $this->asArray();
        }

        if ($this->tempUseSoftDeletes) {
            $builder->where($this->table . '.' . $this->deletedField, null);
        }

        $row  = null;
        $rows = [];

        if (is_array($id)) {
            $rows = $builder->whereIn($this->table . '.' . $this->primaryKey, $id)
                ->get()
                ->getResult($this->tempReturnType);
        } elseif ($singleton) {
            $row = $builder->where($this->table . '.' . $this->primaryKey, $id)
                ->get()
                ->getFirstRow($this->tempReturnType);
        } else {
            $rows = $builder->get()->getResult($this->tempReturnType);
        }

        if ($useCast) {
            $this->tempReturnType = $returnType;

            if ($singleton) {
                if ($row === null) {
                    return null;
                }

                return $this->convertToReturnType($row, $returnType);
            }

            foreach ($rows as $i => $row) {
                $rows[$i] = $this->convertToReturnType($row, $returnType);
            }

            return $rows;
        }

        if ($singleton) {
            return $row;
        }

        return $rows;
    }

    protected function doFindColumn(string $columnName)
    {
        return $this->select($columnName)->asArray()->find();
    }

    
    protected function doFindAll(?int $limit = null, int $offset = 0)
    {
        $limitZeroAsAll = config(Feature::class)->limitZeroAsAll ?? true;
        if ($limitZeroAsAll) {
            $limit ??= 0;
        }

        $builder = $this->builder();

        $useCast = $this->useCasts();
        if ($useCast) {
            $returnType = $this->tempReturnType;
            $this->asArray();
        }

        if ($this->tempUseSoftDeletes) {
            $builder->where($this->table . '.' . $this->deletedField, null);
        }

        $results = $builder->limit($limit, $offset)
            ->get()
            ->getResult($this->tempReturnType);

        if ($useCast) {
            foreach ($results as $i => $row) {
                $results[$i] = $this->convertToReturnType($row, $returnType);
            }

            $this->tempReturnType = $returnType;
        }

        return $results;
    }

    
    protected function doFirst()
    {
        $builder = $this->builder();

        $useCast = $this->useCasts();
        if ($useCast) {
            $returnType = $this->tempReturnType;
            $this->asArray();
        }

        if ($this->tempUseSoftDeletes) {
            $builder->where($this->table . '.' . $this->deletedField, null);
        } elseif ($this->useSoftDeletes && ($builder->QBGroupBy === []) && $this->primaryKey !== '') {
            $builder->groupBy($this->table . '.' . $this->primaryKey);
        }

        
        
        if ($builder->QBGroupBy !== [] && ($builder->QBOrderBy === []) && $this->primaryKey !== '') {
            $builder->orderBy($this->table . '.' . $this->primaryKey, 'asc');
        }

        $row = $builder->limit(1, 0)->get()->getFirstRow($this->tempReturnType);

        if ($useCast && $row !== null) {
            $row = $this->convertToReturnType($row, $returnType);

            $this->tempReturnType = $returnType;
        }

        return $row;
    }

    protected function doInsert(array $row)
    {
        $escape       = $this->escape;
        $this->escape = [];

        
        
        if (! $this->useAutoIncrement) {
            if (! isset($row[$this->primaryKey])) {
                throw DataException::forEmptyPrimaryKey('insert');
            }

            
            $this->validateID($row[$this->primaryKey], false);
        }

        $builder = $this->builder();

        
        foreach ($row as $key => $val) {
            $builder->set($key, $val, $escape[$key] ?? null);
        }

        if ($this->allowEmptyInserts && $row === []) {
            $table = $this->db->protectIdentifiers($this->table, true, null, false);
            if ($this->db->getPlatform() === 'MySQLi') {
                $sql = 'INSERT INTO ' . $table . ' VALUES ()';
            } elseif ($this->db->getPlatform() === 'OCI8') {
                $allFields = $this->db->protectIdentifiers(
                    array_map(
                        static fn ($row) => $row->name,
                        $this->db->getFieldData($this->table),
                    ),
                    false,
                    true,
                );

                $sql = sprintf(
                    'INSERT INTO %s (%s) VALUES (%s)',
                    $table,
                    implode(',', $allFields),
                    substr(str_repeat(',DEFAULT', count($allFields)), 1),
                );
            } else {
                $sql = 'INSERT INTO ' . $table . ' DEFAULT VALUES';
            }

            $result = $this->db->query($sql);
        } else {
            $result = $builder->insert();
        }

        
        if ($result) {
            $this->insertID = $this->useAutoIncrement ? $this->db->insertID() : $row[$this->primaryKey];
        }

        return $result;
    }

    protected function doInsertBatch(?array $set = null, ?bool $escape = null, int $batchSize = 100, bool $testing = false)
    {
        if (is_array($set) && ! $this->useAutoIncrement) {
            foreach ($set as $row) {
                
                
                if (! isset($row[$this->primaryKey])) {
                    throw DataException::forEmptyPrimaryKey('insertBatch');
                }

                
                $this->validateID($row[$this->primaryKey], false);
            }
        }

        return $this->builder()->testMode($testing)->insertBatch($set, $escape, $batchSize);
    }

    protected function doUpdate($id = null, $row = null): bool
    {
        $escape       = $this->escape;
        $this->escape = [];

        $builder = $this->builder();

        if (is_array($id) && $id !== []) {
            $builder = $builder->whereIn($this->table . '.' . $this->primaryKey, $id);
        }

        
        foreach ($row as $key => $val) {
            $builder->set($key, $val, $escape[$key] ?? null);
        }

        if ($builder->getCompiledQBWhere() === []) {
            throw new DatabaseException(
                'Updates are not allowed unless they contain a "where" or "like" clause.',
            );
        }

        return $builder->update();
    }

    protected function doUpdateBatch(?array $set = null, ?string $index = null, int $batchSize = 100, bool $returnSQL = false)
    {
        return $this->builder()->testMode($returnSQL)->updateBatch($set, $index, $batchSize);
    }

    protected function doDelete($id = null, bool $purge = false)
    {
        $set     = [];
        $builder = $this->builder();

        if (is_array($id) && $id !== []) {
            $builder = $builder->whereIn($this->primaryKey, $id);
        }

        if ($this->useSoftDeletes && ! $purge) {
            if ($builder->getCompiledQBWhere() === []) {
                throw new DatabaseException(
                    'Deletes are not allowed unless they contain a "where" or "like" clause.',
                );
            }

            $builder->where($this->deletedField);

            $set[$this->deletedField] = $this->setDate();

            if ($this->useTimestamps && $this->updatedField !== '') {
                $set[$this->updatedField] = $this->setDate();
            }

            return $builder->update($set);
        }

        return $builder->delete();
    }

    protected function doPurgeDeleted()
    {
        return $this->builder()
            ->where($this->table . '.' . $this->deletedField . ' IS NOT NULL')
            ->delete();
    }

    protected function doOnlyDeleted()
    {
        $this->builder()->where($this->table . '.' . $this->deletedField . ' IS NOT NULL');
    }

    protected function doReplace(?array $row = null, bool $returnSQL = false)
    {
        return $this->builder()->testMode($returnSQL)->replace($row);
    }

    
    protected function doErrors()
    {
        
        $error = $this->db->error();

        if ((int) $error['code'] === 0) {
            return [];
        }

        return [$this->db::class => $error['message']];
    }

    public function getIdValue($row)
    {
        if (is_object($row)) {
            
            if ($row instanceof Entity && $row->{$this->primaryKey} !== null) {
                $cast = $row->cast();

                
                $row->cast(false);

                $primaryKey = $row->{$this->primaryKey};

                
                $row->cast($cast);

                return $primaryKey;
            }

            if (! $row instanceof Entity && isset($row->{$this->primaryKey})) {
                return $row->{$this->primaryKey};
            }
        }

        if (is_array($row) && isset($row[$this->primaryKey])) {
            return $row[$this->primaryKey];
        }

        return null;
    }

    public function countAllResults(bool $reset = true, bool $test = false)
    {
        if ($this->tempUseSoftDeletes) {
            $this->builder()->where($this->table . '.' . $this->deletedField, null);
        }

        
        
        
        $this->tempUseSoftDeletes = $reset
            ? $this->useSoftDeletes
            : ($this->useSoftDeletes ? false : $this->useSoftDeletes);

        return $this->builder()->testMode($test)->countAllResults($reset);
    }

    
    public function chunk(int $size, Closure $userFunc)
    {
        if ($size <= 0) {
            throw new InvalidArgumentException('chunk() requires a positive integer for the $size argument.');
        }

        $total  = $this->builder()->countAllResults(false);
        $offset = 0;

        while ($offset < $total) {
            $builder = clone $this->builder();
            $rows    = $builder->get($size, $offset);

            if (! $rows) {
                throw DataException::forEmptyDataset('chunk');
            }

            $rows = $rows->getResult($this->tempReturnType);

            $offset += $size;

            if ($rows === []) {
                continue;
            }

            foreach ($rows as $row) {
                if ($userFunc($row) === false) {
                    return;
                }
            }
        }
    }

    
    public function builder(?string $table = null)
    {
        
        if ($this->builder instanceof BaseBuilder) {
            
            if ((string) $table !== '' && $this->builder->getTable() !== $table) {
                return $this->db->table($table);
            }

            return $this->builder;
        }

        
        
        
        if ($this->primaryKey === '') {
            throw ModelException::forNoPrimaryKey(static::class);
        }

        $table = ((string) $table === '') ? $this->table : $table;

        
        if (! $this->db instanceof BaseConnection) {
            $this->db = Database::connect($this->DBGroup);
        }

        $builder = $this->db->table($table);

        
        if ($table === $this->table) {
            $this->builder = $builder;
        }

        return $builder;
    }

    
    public function set($key, $value = '', ?bool $escape = null)
    {
        if (is_object($key)) {
            $key = $key instanceof stdClass ? (array) $key : $this->objectToArray($key);
        }

        $data = is_array($key) ? $key : [$key => $value];

        foreach (array_keys($data) as $k) {
            $this->tempData['escape'][$k] = $escape;
        }

        $this->tempData['data'] = array_merge($this->tempData['data'] ?? [], $data);

        return $this;
    }

    protected function shouldUpdate($row): bool
    {
        if (parent::shouldUpdate($row) === false) {
            return false;
        }

        if ($this->useAutoIncrement === true) {
            return true;
        }

        
        
        return $this->where($this->primaryKey, $this->getIdValue($row))->countAllResults() === 1;
    }

    public function insert($row = null, bool $returnID = true)
    {
        if (isset($this->tempData['data'])) {
            if ($row === null) {
                $row = $this->tempData['data'];
            } else {
                $row = $this->transformDataToArray($row, 'insert');
                $row = array_merge($this->tempData['data'], $row);
            }
        }

        $this->escape   = $this->tempData['escape'] ?? [];
        $this->tempData = [];

        return parent::insert($row, $returnID);
    }

    protected function doProtectFieldsForInsert(array $row): array
    {
        if (! $this->protectFields) {
            return $row;
        }

        if ($this->allowedFields === []) {
            throw DataException::forInvalidAllowedFields(static::class);
        }

        foreach (array_keys($row) as $key) {
            
            if ($this->useAutoIncrement === false && $key === $this->primaryKey) {
                continue;
            }

            if (! in_array($key, $this->allowedFields, true)) {
                unset($row[$key]);
            }
        }

        return $row;
    }

    public function update($id = null, $row = null): bool
    {
        if (isset($this->tempData['data'])) {
            if ($row === null) {
                $row = $this->tempData['data'];
            } else {
                $row = $this->transformDataToArray($row, 'update');
                $row = array_merge($this->tempData['data'], $row);
            }
        }

        $this->escape   = $this->tempData['escape'] ?? [];
        $this->tempData = [];

        return parent::update($id, $row);
    }

    protected function objectToRawArray($object, bool $onlyChanged = true, bool $recursive = false): array
    {
        return parent::objectToRawArray($object, $onlyChanged);
    }

    
    public function __get(string $name)
    {
        if (parent::__isset($name)) {
            return parent::__get($name);
        }

        return $this->builder()->{$name} ?? null;
    }

    
    public function __isset(string $name): bool
    {
        if (parent::__isset($name)) {
            return true;
        }

        return isset($this->builder()->{$name});
    }

    
    public function __call(string $name, array $params)
    {
        $builder = $this->builder();
        $result  = null;

        if (method_exists($this->db, $name)) {
            $result = $this->db->{$name}(...$params);
        } elseif (method_exists($builder, $name)) {
            $this->checkBuilderMethod($name);

            $result = $builder->{$name}(...$params);
        } else {
            throw new BadMethodCallException('Call to undefined method ' . static::class . '::' . $name);
        }

        if ($result instanceof BaseBuilder) {
            return $this;
        }

        return $result;
    }

    
    private function checkBuilderMethod(string $name): void
    {
        if (in_array($name, $this->builderMethodsNotAvailable, true)) {
            throw ModelException::forMethodNotAvailable(static::class, $name . '()');
        }
    }
}
