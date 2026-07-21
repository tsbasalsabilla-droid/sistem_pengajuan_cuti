<?php

declare(strict_types=1);



namespace CodeIgniter;

use Closure;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseResult;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Database\Query;
use CodeIgniter\Database\RawSql;
use CodeIgniter\DataCaster\Cast\CastInterface;
use CodeIgniter\DataConverter\DataConverter;
use CodeIgniter\Entity\Cast\CastInterface as EntityCastInterface;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Exceptions\ModelException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Pager\Pager;
use CodeIgniter\Validation\ValidationInterface;
use Config\Feature;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use stdClass;


abstract class BaseModel
{
    
    public $pager;

    
    protected $db;

    
    protected $insertID = 0;

    
    protected $DBGroup;

    
    protected $returnType = 'array';

    
    protected $tempReturnType;

    
    protected array $casts = [];

    
    protected array $castHandlers = [];

    protected ?DataConverter $converter = null;

    
    protected $protectFields = true;

    
    protected $allowedFields = [];

    
    protected $useTimestamps = false;

    
    protected $dateFormat = 'datetime';

    
    protected $createdField = 'created_at';

    
    protected $updatedField = 'updated_at';

    
    protected $useSoftDeletes = false;

    
    protected $tempUseSoftDeletes;

    
    protected $deletedField = 'deleted_at';

    
    protected bool $allowEmptyInserts = false;

    
    protected bool $updateOnlyChanged = true;

    
    protected $validationRules = [];

    
    protected $validationMessages = [];

    
    protected $skipValidation = false;

    
    protected $cleanValidationRules = true;

    
    protected $validation;

    

    
    protected $allowCallbacks = true;

    
    protected $tempAllowCallbacks;

    
    protected $beforeInsert = [];

    
    protected $afterInsert = [];

    
    protected $beforeUpdate = [];

    
    protected $afterUpdate = [];

    
    protected $beforeInsertBatch = [];

    
    protected $afterInsertBatch = [];

    
    protected $beforeUpdateBatch = [];

    
    protected $afterUpdateBatch = [];

    
    protected $beforeFind = [];

    
    protected $afterFind = [];

    
    protected $beforeDelete = [];

    
    protected $afterDelete = [];

    public function __construct(?ValidationInterface $validation = null)
    {
        $this->tempReturnType     = $this->returnType;
        $this->tempUseSoftDeletes = $this->useSoftDeletes;
        $this->tempAllowCallbacks = $this->allowCallbacks;

        $this->validation = $validation;

        $this->initialize();
        $this->createDataConverter();
    }

    
    protected function createDataConverter(): void
    {
        if ($this->useCasts()) {
            $this->converter = new DataConverter(
                $this->casts,
                $this->castHandlers,
                $this->db,
            );
        }
    }

    
    protected function useCasts(): bool
    {
        return $this->casts !== [];
    }

    
    protected function initialize()
    {
    }

    
    abstract protected function doFind(bool $singleton, $id = null);

    
    abstract protected function doFindColumn(string $columnName);

    
    abstract protected function doFindAll(?int $limit = null, int $offset = 0);

    
    abstract protected function doFirst();

    
    abstract protected function doInsert(array $row);

    
    abstract protected function doInsertBatch(?array $set = null, ?bool $escape = null, int $batchSize = 100, bool $testing = false);

    
    abstract protected function doUpdate($id = null, $row = null): bool;

    
    abstract protected function doUpdateBatch(?array $set = null, ?string $index = null, int $batchSize = 100, bool $returnSQL = false);

    
    abstract protected function doDelete($id = null, bool $purge = false);

    
    abstract protected function doPurgeDeleted();

    
    abstract protected function doOnlyDeleted();

    
    abstract protected function doReplace(?array $row = null, bool $returnSQL = false);

    
    abstract protected function doErrors();

    
    abstract public function getIdValue($row);

    
    abstract public function countAllResults(bool $reset = true, bool $test = false);

    
    abstract public function chunk(int $size, Closure $userFunc);

    
    public function find($id = null)
    {
        $singleton = is_numeric($id) || is_string($id);

        if ($this->tempAllowCallbacks) {
            
            $eventData = $this->trigger('beforeFind', [
                'id'        => $id,
                'method'    => 'find',
                'singleton' => $singleton,
            ]);

            if (isset($eventData['returnData']) && $eventData['returnData'] === true) {
                return $eventData['data'];
            }
        }

        $eventData = [
            'id'        => $id,
            'data'      => $this->doFind($singleton, $id),
            'method'    => 'find',
            'singleton' => $singleton,
        ];

        if ($this->tempAllowCallbacks) {
            $eventData = $this->trigger('afterFind', $eventData);
        }

        $this->tempReturnType     = $this->returnType;
        $this->tempUseSoftDeletes = $this->useSoftDeletes;
        $this->tempAllowCallbacks = $this->allowCallbacks;

        return $eventData['data'];
    }

    
    public function findColumn(string $columnName)
    {
        if (str_contains($columnName, ',')) {
            throw DataException::forFindColumnHaveMultipleColumns();
        }

        $resultSet = $this->doFindColumn($columnName);

        return $resultSet !== null ? array_column($resultSet, $columnName) : null;
    }

    
    public function findAll(?int $limit = null, int $offset = 0)
    {
        $limitZeroAsAll = config(Feature::class)->limitZeroAsAll ?? true;
        if ($limitZeroAsAll) {
            $limit ??= 0;
        }

        if ($this->tempAllowCallbacks) {
            
            $eventData = $this->trigger('beforeFind', [
                'method'    => 'findAll',
                'limit'     => $limit,
                'offset'    => $offset,
                'singleton' => false,
            ]);

            if (isset($eventData['returnData']) && $eventData['returnData'] === true) {
                return $eventData['data'];
            }
        }

        $eventData = [
            'data'      => $this->doFindAll($limit, $offset),
            'limit'     => $limit,
            'offset'    => $offset,
            'method'    => 'findAll',
            'singleton' => false,
        ];

        if ($this->tempAllowCallbacks) {
            $eventData = $this->trigger('afterFind', $eventData);
        }

        $this->tempReturnType     = $this->returnType;
        $this->tempUseSoftDeletes = $this->useSoftDeletes;
        $this->tempAllowCallbacks = $this->allowCallbacks;

        return $eventData['data'];
    }

    
    public function first()
    {
        if ($this->tempAllowCallbacks) {
            
            $eventData = $this->trigger('beforeFind', [
                'method'    => 'first',
                'singleton' => true,
            ]);

            if (isset($eventData['returnData']) && $eventData['returnData'] === true) {
                return $eventData['data'];
            }
        }

        $eventData = [
            'data'      => $this->doFirst(),
            'method'    => 'first',
            'singleton' => true,
        ];

        if ($this->tempAllowCallbacks) {
            $eventData = $this->trigger('afterFind', $eventData);
        }

        $this->tempReturnType     = $this->returnType;
        $this->tempUseSoftDeletes = $this->useSoftDeletes;
        $this->tempAllowCallbacks = $this->allowCallbacks;

        return $eventData['data'];
    }

    
    public function save($row): bool
    {
        if ((array) $row === []) {
            return true;
        }

        if ($this->shouldUpdate($row)) {
            $response = $this->update($this->getIdValue($row), $row);
        } else {
            $response = $this->insert($row, false);

            if ($response !== false) {
                $response = true;
            }
        }

        return $response;
    }

    
    protected function shouldUpdate($row): bool
    {
        $id = $this->getIdValue($row);

        return ! in_array($id, [null, [], ''], true);
    }

    
    public function getInsertID()
    {
        return is_numeric($this->insertID) ? (int) $this->insertID : $this->insertID;
    }

    
    protected function validateID(mixed $id, bool $allowArray = true): void
    {
        if (is_array($id)) {
            
            if (! $allowArray) {
                throw new InvalidArgumentException(
                    'Invalid primary key: only a single value is allowed, not an array.',
                );
            }

            
            if ($id === []) {
                throw new InvalidArgumentException('Invalid primary key: cannot be an empty array.');
            }

            
            foreach ($id as $key => $valueId) {
                if (is_array($valueId)) {
                    throw new InvalidArgumentException(
                        sprintf('Invalid primary key at index %s: nested arrays are not allowed.', $key),
                    );
                }

                
                $this->validateID($valueId, false);
            }

            return;
        }

        
        if ($id instanceof RawSql) {
            return;
        }

        
        if (in_array($id, [null, 0, '0', '', true, false], true)) {
            $type = is_bool($id) ? 'boolean ' . var_export($id, true) : var_export($id, true);

            throw new InvalidArgumentException(
                sprintf('Invalid primary key: %s is not allowed.', $type),
            );
        }

        
        if (! is_int($id) && ! is_string($id)) {
            throw new InvalidArgumentException(
                sprintf('Invalid primary key: must be int or string, %s given.', get_debug_type($id)),
            );
        }
    }

    
    public function insert($row = null, bool $returnID = true)
    {
        $this->insertID = 0;

        
        $cleanValidationRules       = $this->cleanValidationRules;
        $this->cleanValidationRules = false;

        $row = $this->transformDataToArray($row, 'insert');

        
        if (! $this->skipValidation && ! $this->validate($row)) {
            
            $this->cleanValidationRules = $cleanValidationRules;

            return false;
        }

        
        $this->cleanValidationRules = $cleanValidationRules;

        
        
        $row = $this->doProtectFieldsForInsert($row);

        
        
        if (! $this->allowEmptyInserts && $row === []) {
            throw DataException::forEmptyDataset('insert');
        }

        
        $date = $this->setDate();
        $row  = $this->setCreatedField($row, $date);
        $row  = $this->setUpdatedField($row, $date);

        $eventData = ['data' => $row];

        if ($this->tempAllowCallbacks) {
            $eventData = $this->trigger('beforeInsert', $eventData);
        }

        $result = $this->doInsert($eventData['data']);

        $eventData = [
            'id'     => $this->insertID,
            'data'   => $eventData['data'],
            'result' => $result,
        ];

        if ($this->tempAllowCallbacks) {
            
            $this->trigger('afterInsert', $eventData);
        }

        $this->tempAllowCallbacks = $this->allowCallbacks;

        
        if (! $result) {
            return $result;
        }

        
        return $returnID ? $this->insertID : $result;
    }

    
    protected function setCreatedField(array $row, $date): array
    {
        if ($this->useTimestamps && $this->createdField !== '' && ! array_key_exists($this->createdField, $row)) {
            $row[$this->createdField] = $date;
        }

        return $row;
    }

    
    protected function setUpdatedField(array $row, $date): array
    {
        if ($this->useTimestamps && $this->updatedField !== '' && ! array_key_exists($this->updatedField, $row)) {
            $row[$this->updatedField] = $date;
        }

        return $row;
    }

    
    public function insertBatch(?array $set = null, ?bool $escape = null, int $batchSize = 100, bool $testing = false)
    {
        
        $cleanValidationRules       = $this->cleanValidationRules;
        $this->cleanValidationRules = false;

        if (is_array($set)) {
            foreach ($set as &$row) {
                $row = $this->transformDataToArray($row, 'insert');

                
                if (! $this->skipValidation && ! $this->validate($row)) {
                    
                    $this->cleanValidationRules = $cleanValidationRules;

                    return false;
                }

                
                
                $row = $this->doProtectFieldsForInsert($row);

                
                $date = $this->setDate();
                $row  = $this->setCreatedField($row, $date);
                $row  = $this->setUpdatedField($row, $date);
            }
        }

        
        $this->cleanValidationRules = $cleanValidationRules;

        $eventData = ['data' => $set];

        if ($this->tempAllowCallbacks) {
            $eventData = $this->trigger('beforeInsertBatch', $eventData);
        }

        $result = $this->doInsertBatch($eventData['data'], $escape, $batchSize, $testing);

        $eventData = [
            'data'   => $eventData['data'],
            'result' => $result,
        ];

        if ($this->tempAllowCallbacks) {
            
            $this->trigger('afterInsertBatch', $eventData);
        }

        $this->tempAllowCallbacks = $this->allowCallbacks;

        return $result;
    }

    
    public function update($id = null, $row = null): bool
    {
        if ($id !== null) {
            if (! is_array($id)) {
                $id = [$id];
            }

            $this->validateID($id);
        }

        $row = $this->transformDataToArray($row, 'update');

        
        if (! $this->skipValidation && ! $this->validate($row)) {
            return false;
        }

        
        
        $row = $this->doProtectFields($row);

        
        
        if ($row === []) {
            throw DataException::forEmptyDataset('update');
        }

        $row = $this->setUpdatedField($row, $this->setDate());

        $eventData = [
            'id'   => $id,
            'data' => $row,
        ];

        if ($this->tempAllowCallbacks) {
            $eventData = $this->trigger('beforeUpdate', $eventData);
        }

        $eventData = [
            'id'     => $id,
            'data'   => $eventData['data'],
            'result' => $this->doUpdate($id, $eventData['data']),
        ];

        if ($this->tempAllowCallbacks) {
            $this->trigger('afterUpdate', $eventData);
        }

        $this->tempAllowCallbacks = $this->allowCallbacks;

        return $eventData['result'];
    }

    
    public function updateBatch(?array $set = null, ?string $index = null, int $batchSize = 100, bool $returnSQL = false)
    {
        if (is_array($set)) {
            foreach ($set as &$row) {
                
                
                
                $updateIndex = null;

                if ($this->updateOnlyChanged) {
                    if (is_array($row)) {
                        $updateIndex = $row[$index] ?? null;
                    } elseif ($row instanceof Entity) {
                        $updateIndex = $row->toRawArray()[$index] ?? null;
                    } elseif (is_object($row)) {
                        $updateIndex = $row->{$index} ?? null;
                    }
                }

                $row = $this->transformDataToArray($row, 'update');

                
                if (! $this->skipValidation && ! $this->validate($row)) {
                    return false;
                }

                
                
                if ($updateIndex !== null) {
                    $row[$index] = $updateIndex;
                } else {
                    $updateIndex = $row[$index] ?? null;
                }

                if ($updateIndex === null) {
                    throw new InvalidArgumentException(
                        'The index ("' . $index . '") for updateBatch() is missing in the data: '
                        . json_encode($row),
                    );
                }

                
                
                $row = $this->doProtectFields($row);

                
                $row[$index] = $updateIndex;

                $row = $this->setUpdatedField($row, $this->setDate());
            }
        }

        $eventData = ['data' => $set];

        if ($this->tempAllowCallbacks) {
            $eventData = $this->trigger('beforeUpdateBatch', $eventData);
        }

        $result = $this->doUpdateBatch($eventData['data'], $index, $batchSize, $returnSQL);

        $eventData = [
            'data'   => $eventData['data'],
            'result' => $result,
        ];

        if ($this->tempAllowCallbacks) {
            
            $this->trigger('afterUpdateBatch', $eventData);
        }

        $this->tempAllowCallbacks = $this->allowCallbacks;

        return $result;
    }

    
    public function delete($id = null, bool $purge = false)
    {
        if ($id !== null) {
            if (! is_array($id)) {
                $id = [$id];
            }

            $this->validateID($id);
        }

        $eventData = [
            'id'    => $id,
            'purge' => $purge,
        ];

        if ($this->tempAllowCallbacks) {
            $this->trigger('beforeDelete', $eventData);
        }

        $eventData = [
            'id'     => $id,
            'data'   => null,
            'purge'  => $purge,
            'result' => $this->doDelete($id, $purge),
        ];

        if ($this->tempAllowCallbacks) {
            $this->trigger('afterDelete', $eventData);
        }

        $this->tempAllowCallbacks = $this->allowCallbacks;

        return $eventData['result'];
    }

    
    public function purgeDeleted()
    {
        if (! $this->useSoftDeletes) {
            return true;
        }

        return $this->doPurgeDeleted();
    }

    
    public function withDeleted(bool $val = true)
    {
        $this->tempUseSoftDeletes = ! $val;

        return $this;
    }

    
    public function onlyDeleted()
    {
        $this->tempUseSoftDeletes = false;
        $this->doOnlyDeleted();

        return $this;
    }

    
    public function replace(?array $row = null, bool $returnSQL = false)
    {
        
        if (($row !== null) && ! $this->skipValidation && ! $this->validate($row)) {
            return false;
        }

        $row = (array) $row;
        $row = $this->setCreatedField($row, $this->setDate());
        $row = $this->setUpdatedField($row, $this->setDate());

        return $this->doReplace($row, $returnSQL);
    }

    
    public function errors(bool $forceDB = false)
    {
        if ($this->validation === null) {
            return $this->doErrors();
        }

        
        if (! $forceDB && ! $this->skipValidation && ($errors = $this->validation->getErrors()) !== []) {
            return $errors;
        }

        return $this->doErrors();
    }

    
    public function paginate(?int $perPage = null, string $group = 'default', ?int $page = null, int $segment = 0)
    {
        
        $pager = service('pager');

        if ($segment !== 0) {
            $pager->setSegment($segment, $group);
        }

        $page = $page >= 1 ? $page : $pager->getCurrentPage($group);
        
        $this->pager = $pager->store($group, $page, $perPage, $this->countAllResults(false), $segment);
        $perPage     = $this->pager->getPerPage($group);
        $offset      = ($pager->getCurrentPage($group) - 1) * $perPage;

        return $this->findAll($perPage, $offset);
    }

    
    public function setAllowedFields(array $allowedFields)
    {
        $this->allowedFields = $allowedFields;

        return $this;
    }

    
    public function protect(bool $protect = true)
    {
        $this->protectFields = $protect;

        return $this;
    }

    
    protected function doProtectFields(array $row): array
    {
        if (! $this->protectFields) {
            return $row;
        }

        if ($this->allowedFields === []) {
            throw DataException::forInvalidAllowedFields(static::class);
        }

        foreach (array_keys($row) as $key) {
            if (! in_array($key, $this->allowedFields, true)) {
                unset($row[$key]);
            }
        }

        return $row;
    }

    
    protected function doProtectFieldsForInsert(array $row): array
    {
        return $this->doProtectFields($row);
    }

    
    protected function setDate(?int $userDate = null)
    {
        $currentDate = $userDate ?? Time::now()->getTimestamp();

        return $this->intToDate($currentDate);
    }

    
    protected function intToDate(int $value)
    {
        return match ($this->dateFormat) {
            'int'      => $value,
            'datetime' => date($this->db->dateFormat['datetime'], $value),
            'date'     => date($this->db->dateFormat['date'], $value),
            default    => throw ModelException::forNoDateFormat(static::class),
        };
    }

    
    protected function timeToDate(Time $value)
    {
        return match ($this->dateFormat) {
            'datetime' => $value->format($this->db->dateFormat['datetime']),
            'date'     => $value->format($this->db->dateFormat['date']),
            'int'      => $value->getTimestamp(),
            default    => (string) $value,
        };
    }

    
    public function skipValidation(bool $skip = true)
    {
        $this->skipValidation = $skip;

        return $this;
    }

    
    public function setValidationMessages(array $validationMessages)
    {
        $this->validationMessages = $validationMessages;

        return $this;
    }

    
    public function setValidationMessage(string $field, array $fieldMessages)
    {
        $this->validationMessages[$field] = $fieldMessages;

        return $this;
    }

    
    public function setValidationRules(array $validationRules)
    {
        $this->validationRules = $validationRules;

        return $this;
    }

    
    public function setValidationRule(string $field, $fieldRules)
    {
        $rules = $this->validationRules;

        
        
        if (is_string($rules)) {
            $this->ensureValidation();

            [$rules, $customErrors] = $this->validation->loadRuleGroup($rules);

            $this->validationRules = $rules;
            $this->validationMessages += $customErrors;
        }

        $this->validationRules[$field] = $fieldRules;

        return $this;
    }

    
    public function cleanRules(bool $choice = false)
    {
        $this->cleanValidationRules = $choice;

        return $this;
    }

    
    public function validate($row): bool
    {
        if ($this->skipValidation) {
            return true;
        }

        $rules = $this->getValidationRules();

        if ($rules === []) {
            return true;
        }

        
        if (is_object($row)) {
            $row = (array) $row;
        }

        if ($row === []) {
            return true;
        }

        $rules = $this->cleanValidationRules ? $this->cleanValidationRules($rules, $row) : $rules;

        
        
        if ($rules === []) {
            return true;
        }

        $this->ensureValidation();

        $this->validation->reset()->setRules($rules, $this->validationMessages);

        return $this->validation->run($row, null, $this->DBGroup);
    }

    
    public function getValidationRules(array $options = []): array
    {
        $rules = $this->validationRules;

        
        
        if (is_string($rules)) {
            $this->ensureValidation();

            [$rules, $customErrors] = $this->validation->loadRuleGroup($rules);

            $this->validationMessages += $customErrors;
        }

        if (isset($options['except'])) {
            $rules = array_diff_key($rules, array_flip($options['except']));
        } elseif (isset($options['only'])) {
            $rules = array_intersect_key($rules, array_flip($options['only']));
        }

        return $rules;
    }

    protected function ensureValidation(): void
    {
        if ($this->validation === null) {
            $this->validation = service('validation', null, false);
        }
    }

    
    public function getValidationMessages(): array
    {
        return $this->validationMessages;
    }

    
    protected function cleanValidationRules(array $rules, array $row): array
    {
        if ($row === []) {
            return [];
        }

        foreach (array_keys($rules) as $field) {
            if (! array_key_exists($field, $row)) {
                unset($rules[$field]);
            }
        }

        return $rules;
    }

    
    public function allowCallbacks(bool $val = true)
    {
        $this->tempAllowCallbacks = $val;

        return $this;
    }

    
    protected function trigger(string $event, array $eventData)
    {
        
        if (! isset($this->{$event}) || $this->{$event} === []) {
            return $eventData;
        }

        foreach ($this->{$event} as $callback) {
            if (! method_exists($this, $callback)) {
                throw DataException::forInvalidMethodTriggered($callback);
            }

            $eventData = $this->{$callback}($eventData);
        }

        return $eventData;
    }

    
    public function asArray()
    {
        $this->tempReturnType = 'array';

        return $this;
    }

    
    public function asObject(string $class = 'object')
    {
        $this->tempReturnType = $class;

        return $this;
    }

    
    protected function objectToArray($object, bool $onlyChanged = true, bool $recursive = false): array
    {
        $properties = $this->objectToRawArray($object, $onlyChanged, $recursive);

        
        return $this->timeToString($properties);
    }

    
    protected function timeToString(array $properties): array
    {
        if ($properties === []) {
            return [];
        }

        return array_map(function ($value) {
            if ($value instanceof Time) {
                return $this->timeToDate($value);
            }

            return $value;
        }, $properties);
    }

    
    protected function objectToRawArray($object, bool $onlyChanged = true, bool $recursive = false): array
    {
        
        if (method_exists($object, 'toRawArray')) {
            $properties = $object->toRawArray($onlyChanged, $recursive);
        } else {
            $mirror = new ReflectionClass($object);
            $props  = $mirror->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

            $properties = [];

            
            
            foreach ($props as $prop) {
                $properties[$prop->getName()] = $prop->getValue($object);
            }
        }

        return $properties;
    }

    
    protected function transformDataToArray($row, string $type): array
    {
        if (! in_array($type, ['insert', 'update'], true)) {
            throw new InvalidArgumentException(sprintf('Invalid type "%s" used upon transforming data to array.', $type));
        }

        if (! $this->allowEmptyInserts && ($row === null || (array) $row === [])) {
            throw DataException::forEmptyDataset($type);
        }

        
        if ($this->skipValidation === false && $this->cleanValidationRules === false) {
            $onlyChanged = false;
        } else {
            $onlyChanged = ($type === 'update' && $this->updateOnlyChanged);
        }

        if ($this->useCasts()) {
            if (is_array($row)) {
                $row = $this->converter->toDataSource($row);
            } elseif ($row instanceof stdClass) {
                $row = (array) $row;
                $row = $this->converter->toDataSource($row);
            } elseif ($row instanceof Entity) {
                $row = $this->converter->extract($row, $onlyChanged);
            } elseif (is_object($row)) {
                $row = $this->converter->extract($row, $onlyChanged);
            }
        }
        
        
        
        elseif (is_object($row) && ! $row instanceof stdClass) {
            $row = $this->objectToArray($row, $onlyChanged, true);
        }

        
        
        
        if (is_object($row)) {
            $row = (array) $row;
        }

        
        if (! $this->allowEmptyInserts && ($row === null || $row === [])) {
            throw DataException::forEmptyDataset($type);
        }

        
        return $this->timeToString($row);
    }

    
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        return $this->db->{$name} ?? null;
    }

    
    public function __isset(string $name): bool
    {
        if (property_exists($this, $name)) {
            return true;
        }

        return isset($this->db->{$name});
    }

    
    public function __call(string $name, array $params)
    {
        if (method_exists($this->db, $name)) {
            return $this->db->{$name}(...$params);
        }

        return null;
    }

    
    public function allowEmptyInserts(bool $value = true): self
    {
        $this->allowEmptyInserts = $value;

        return $this;
    }

    
    protected function convertToReturnType(array $row, string $returnType): array|object
    {
        if ($returnType === 'array') {
            return $this->converter->fromDataSource($row);
        }

        if ($returnType === 'object') {
            return (object) $this->converter->fromDataSource($row);
        }

        return $this->converter->reconstruct($returnType, $row);
    }
}
