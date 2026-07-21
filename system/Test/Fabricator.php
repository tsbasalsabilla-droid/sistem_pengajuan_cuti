<?php

declare(strict_types=1);



namespace CodeIgniter\Test;

use Closure;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Exceptions\RuntimeException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Model;
use Config\App;
use Faker\Factory;
use Faker\Generator;
use InvalidArgumentException as BaseInvalidArgumentException;


class Fabricator
{
    
    protected static $tableCounts = [];

    
    protected $faker;

    
    protected $model;

    
    protected $locale;

    
    protected $formatters;

    
    protected $dateFields = [];

    
    protected $overrides = [];

    
    protected $tempOverrides;

    
    private array $modifiedFields = ['unique' => [], 'optional' => [], 'valid' => []];

    
    public $defaultFormatter = 'word';

    
    public function __construct($model, ?array $formatters = null, ?string $locale = null)
    {
        if (is_string($model) && class_exists($model)) {
            $model = model($model, false);
        }

        if (! is_object($model)) {
            throw new InvalidArgumentException(lang('Fabricator.invalidModel'));
        }

        $this->model = $model;

        
        if ($locale === null) {
            $locale = config(App::class)->defaultLocale;
        }

        
        $this->locale = $locale;

        
        $this->faker = Factory::create($this->locale);

        
        foreach (['createdField', 'updatedField', 'deletedField'] as $field) {
            if (isset($this->model->{$field})) {
                $this->dateFields[] = $this->model->{$field};
            }
        }

        
        $this->setFormatters($formatters);
    }

    
    public static function resetCounts()
    {
        self::$tableCounts = [];
    }

    
    public static function getCount(string $table): int
    {
        return self::$tableCounts[$table] ?? 0;
    }

    
    public static function setCount(string $table, int $count): int
    {
        self::$tableCounts[$table] = $count;

        return $count;
    }

    
    public static function upCount(string $table): int
    {
        return self::setCount($table, self::getCount($table) + 1);
    }

    
    public static function downCount(string $table): int
    {
        return self::setCount($table, self::getCount($table) - 1);
    }

    
    public function getModel()
    {
        return $this->model;
    }

    
    public function getLocale(): string
    {
        return $this->locale;
    }

    
    public function getFaker(): Generator
    {
        return $this->faker;
    }

    
    public function getOverrides(): array
    {
        $overrides = $this->tempOverrides ?? $this->overrides;

        $this->tempOverrides = $this->overrides;

        return $overrides;
    }

    
    public function setOverrides(array $overrides = [], $persist = true): self
    {
        if ($persist) {
            $this->overrides = $overrides;
        }

        $this->tempOverrides = $overrides;

        return $this;
    }

    
    public function setUnique(string $field, bool $reset = false, int $maxRetries = 10000): static
    {
        $this->modifiedFields['unique'][$field] = compact('reset', 'maxRetries');

        return $this;
    }

    
    public function setOptional(string $field, float $weight = 0.5, mixed $default = null): static
    {
        $this->modifiedFields['optional'][$field] = compact('weight', 'default');

        return $this;
    }

    
    public function setValid(string $field, ?Closure $validator = null, int $maxRetries = 10000): static
    {
        $this->modifiedFields['valid'][$field] = compact('validator', 'maxRetries');

        return $this;
    }

    
    public function getFormatters(): ?array
    {
        return $this->formatters;
    }

    
    public function setFormatters(?array $formatters = null): self
    {
        if ($formatters !== null) {
            $this->formatters = $formatters;
        } elseif (method_exists($this->model, 'fake')) {
            $this->formatters = null;
        } else {
            $this->detectFormatters();
        }

        return $this;
    }

    
    protected function detectFormatters(): self
    {
        $this->formatters = [];

        if (isset($this->model->allowedFields)) {
            foreach ($this->model->allowedFields as $field) {
                $this->formatters[$field] = $this->guessFormatter($field);
            }
        }

        return $this;
    }

    
    protected function guessFormatter($field): string
    {
        
        try {
            $this->faker->getFormatter($field);

            return $field;
        } catch (BaseInvalidArgumentException) {
            
        }

        
        if (in_array($field, $this->dateFields, true)) {
            switch ($this->model->dateFormat) {
                case 'datetime':
                case 'date':
                    return 'date';

                case 'int':
                    return 'unixTime';
            }
        } elseif ($field === $this->model->primaryKey) {
            return 'numberBetween';
        }

        
        foreach (['email', 'name', 'title', 'text', 'date', 'url'] as $term) {
            if (str_contains(strtolower($field), strtolower($term))) {
                return $term;
            }
        }

        if (str_contains(strtolower($field), 'phone')) {
            return 'phoneNumber';
        }

        
        return $this->defaultFormatter;
    }

    
    public function make(?int $count = null)
    {
        
        if ($count === null) {
            return $this->model->returnType === 'array'
                ? $this->makeArray()
                : $this->makeObject();
        }

        $return = [];

        for ($i = 0; $i < $count; $i++) {
            $return[] = $this->model->returnType === 'array'
                ? $this->makeArray()
                : $this->makeObject();
        }

        return $return;
    }

    
    public function makeArray()
    {
        if ($this->formatters !== null) {
            $result = [];

            foreach ($this->formatters as $field => $formatter) {
                $faker = $this->faker;

                if (isset($this->modifiedFields['unique'][$field])) {
                    $faker = $faker->unique(
                        $this->modifiedFields['unique'][$field]['reset'],
                        $this->modifiedFields['unique'][$field]['maxRetries'],
                    );
                }

                if (isset($this->modifiedFields['optional'][$field])) {
                    $faker = $faker->optional(
                        $this->modifiedFields['optional'][$field]['weight'],
                        $this->modifiedFields['optional'][$field]['default'],
                    );
                }

                if (isset($this->modifiedFields['valid'][$field])) {
                    $faker = $faker->valid(
                        $this->modifiedFields['valid'][$field]['validator'],
                        $this->modifiedFields['valid'][$field]['maxRetries'],
                    );
                }

                $result[$field] = $faker->format($formatter);
            }
        }
        
        elseif (method_exists($this->model, 'fake')) {
            $result = $this->model->fake($this->faker);

            $result = is_object($result) && method_exists($result, 'toArray')
                
                ? $result->toArray()
                
                : (array) $result;
        }
        
        else {
            throw new RuntimeException(lang('Fabricator.missingFormatters'));
        }

        
        return array_merge($result, $this->getOverrides());
    }

    
    public function makeObject(?string $className = null): object
    {
        if ($className === null) {
            if ($this->model->returnType === 'object' || $this->model->returnType === 'array') {
                $className = 'stdClass';
            } else {
                $className = $this->model->returnType;
            }
        }

        
        if ($this->formatters === null && method_exists($this->model, 'fake')) {
            $result = $this->model->fake($this->faker);

            if ($result instanceof $className) {
                
                foreach ($this->getOverrides() as $key => $value) {
                    $result->{$key} = $value;
                }

                return $result;
            }
        }

        
        $array  = $this->makeArray();
        $object = new $className();

        
        if (method_exists($object, 'fill')) {
            $object->fill($array);
        } else {
            foreach ($array as $key => $value) {
                $object->{$key} = $value;
            }
        }

        return $object;
    }

    
    public function create(?int $count = null, bool $mock = false)
    {
        
        if ($mock) {
            return $this->createMock($count);
        }

        $ids = [];

        
        foreach ($this->make($count ?? 1) as $result) {
            if ($id = $this->model->insert($result, true)) {
                $ids[] = $id;
                self::upCount($this->model->table);

                continue;
            }

            throw FrameworkException::forFabricatorCreateFailed($this->model->table, implode(' ', $this->model->errors() ?? []));
        }

        
        if (method_exists($this->model, 'withDeleted')) {
            $this->model->withDeleted();
        }

        return $this->model->find($count === null ? reset($ids) : $ids);
    }

    
    protected function createMock(?int $count = null)
    {
        $datetime = match ($this->model->dateFormat) {
            'datetime' => date('Y-m-d H:i:s'),
            'date'     => date('Y-m-d'),
            default    => Time::now()->getTimestamp(),
        };

        
        $fields = [];

        if ($this->model->useTimestamps) {
            $fields[$this->model->createdField] = $datetime;
            $fields[$this->model->updatedField] = $datetime;
        }

        if ($this->model->useSoftDeletes) {
            $fields[$this->model->deletedField] = null;
        }

        
        $return = [];

        foreach ($this->make($count ?? 1) as $i => $result) {
            
            $fields[$this->model->primaryKey] = $i;

            
            if (is_array($result)) {
                $result = array_merge($result, $fields);
            } else {
                foreach ($fields as $key => $value) {
                    $result->{$key} = $value;
                }
            }

            $return[] = $result;
        }

        return $count === null ? reset($return) : $return;
    }
}
