<?php

declare(strict_types=1);



namespace CodeIgniter\Entity;

use BackedEnum;
use CodeIgniter\DataCaster\DataCaster;
use CodeIgniter\Entity\Cast\ArrayCast;
use CodeIgniter\Entity\Cast\BooleanCast;
use CodeIgniter\Entity\Cast\CSVCast;
use CodeIgniter\Entity\Cast\DatetimeCast;
use CodeIgniter\Entity\Cast\EnumCast;
use CodeIgniter\Entity\Cast\FloatCast;
use CodeIgniter\Entity\Cast\IntBoolCast;
use CodeIgniter\Entity\Cast\IntegerCast;
use CodeIgniter\Entity\Cast\JsonCast;
use CodeIgniter\Entity\Cast\ObjectCast;
use CodeIgniter\Entity\Cast\StringCast;
use CodeIgniter\Entity\Cast\TimestampCast;
use CodeIgniter\Entity\Cast\URICast;
use CodeIgniter\Entity\Exceptions\CastException;
use CodeIgniter\I18n\Time;
use DateTimeInterface;
use Exception;
use JsonSerializable;
use Traversable;
use UnitEnum;


class Entity implements JsonSerializable
{
    
    protected $datamap = [];

    
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    
    protected $casts = [];

    
    protected $castHandlers = [];

    
    private array $defaultCastHandlers = [
        'array'     => ArrayCast::class,
        'bool'      => BooleanCast::class,
        'boolean'   => BooleanCast::class,
        'csv'       => CSVCast::class,
        'datetime'  => DatetimeCast::class,
        'double'    => FloatCast::class,
        'enum'      => EnumCast::class,
        'float'     => FloatCast::class,
        'int'       => IntegerCast::class,
        'integer'   => IntegerCast::class,
        'int-bool'  => IntBoolCast::class,
        'json'      => JsonCast::class,
        'object'    => ObjectCast::class,
        'string'    => StringCast::class,
        'timestamp' => TimestampCast::class,
        'uri'       => URICast::class,
    ];

    
    protected $attributes = [];

    
    protected $original = [];

    
    protected ?DataCaster $dataCaster = null;

    
    private bool $_cast = true;

    
    private bool $_onlyScalars = true;

    
    public function __construct(?array $data = null)
    {
        $this->dataCaster = $this->dataCaster();

        $this->syncOriginal();

        $this->fill($data);
    }

    
    public function fill(?array $data = null)
    {
        if (! is_array($data)) {
            return $this;
        }

        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }

        return $this;
    }

    
    public function toArray(bool $onlyChanged = false, bool $cast = true, bool $recursive = false): array
    {
        $originalCast = $this->_cast;
        $this->_cast  = $cast;

        $keys = array_filter(array_keys($this->attributes), static fn ($key): bool => ! str_starts_with($key, '_'));

        if (is_array($this->datamap)) {
            $keys = array_unique(
                [...array_diff($keys, $this->datamap), ...array_keys($this->datamap)],
            );
        }

        $return = [];

        
        foreach ($keys as $key) {
            if ($onlyChanged && ! $this->hasChanged($key)) {
                continue;
            }

            $return[$key] = $this->__get($key);

            if ($recursive) {
                if ($return[$key] instanceof self) {
                    $return[$key] = $return[$key]->toArray($onlyChanged, $cast, $recursive);
                } elseif (is_callable([$return[$key], 'toArray'])) {
                    $return[$key] = $return[$key]->toArray();
                }
            }
        }

        $this->_cast = $originalCast;

        return $return;
    }

    
    public function toRawArray(bool $onlyChanged = false, bool $recursive = false): array
    {
        $convert = static function ($value) use (&$convert, $recursive) {
            if (! $recursive) {
                return $value;
            }

            if ($value instanceof self) {
                
                return $value->toRawArray(false, true);
            }

            if (is_array($value)) {
                $result = [];

                foreach ($value as $k => $v) {
                    $result[$k] = $convert($v);
                }

                return $result;
            }

            if (is_object($value) && is_callable([$value, 'toRawArray'])) {
                return $value->toRawArray();
            }

            return $value;
        };

        
        if (! $onlyChanged) {
            return $recursive
                ? array_map($convert, $this->attributes)
                : $this->attributes;
        }

        
        $return = [];

        foreach ($this->attributes as $key => $value) {
            
            
            if ($recursive && is_array($value) && $this->containsOnlyEntities($value)) {
                $originalValue = $this->original[$key] ?? null;

                if (! is_string($originalValue)) {
                    
                    $converted = [];

                    foreach ($value as $idx => $item) {
                        $converted[$idx] = $item->toRawArray(false, true);
                    }
                    $return[$key] = $converted;

                    continue;
                }

                
                $originalArray = json_decode($originalValue, true);
                $converted     = [];

                foreach ($value as $idx => $item) {
                    
                    $currentNormalized  = $this->normalizeValue($item);
                    $originalNormalized = $originalArray[$idx] ?? null;

                    
                    if ($originalNormalized === null || $currentNormalized !== $originalNormalized) {
                        $converted[$idx] = $item->toRawArray(false, true);
                    }
                }

                
                if ($converted !== []) {
                    $return[$key] = $converted;
                }

                continue;
            }

            
            if (! $this->hasChanged($key)) {
                continue;
            }

            if ($recursive) {
                
                if (is_array($value)) {
                    $converted = [];

                    foreach ($value as $idx => $item) {
                        $converted[$idx] = $item instanceof self ? $item->toRawArray(false, true) : $convert($item);
                    }
                    $return[$key] = $converted;

                    continue;
                }

                
                $return[$key] = $convert($value);

                continue;
            }

            
            $return[$key] = $value;
        }

        return $return;
    }

    
    public function syncOriginal()
    {
        $this->original     = [];
        $this->_onlyScalars = true;

        foreach ($this->attributes as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $this->original[$key] = json_encode($this->normalizeValue($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $this->_onlyScalars   = false;
            } else {
                $this->original[$key] = $value;
            }
        }

        return $this;
    }

    
    public function hasChanged(?string $key = null): bool
    {
        
        if ($key === null) {
            if ($this->_onlyScalars) {
                return $this->original !== $this->attributes;
            }

            foreach (array_keys($this->attributes) as $attributeKey) {
                if ($this->hasChanged($attributeKey)) {
                    return true;
                }
            }

            return false;
        }

        $dbColumn = $this->mapProperty($key);

        
        if (! array_key_exists($dbColumn, $this->original) && ! array_key_exists($dbColumn, $this->attributes)) {
            return false;
        }

        
        if (! array_key_exists($dbColumn, $this->original) && array_key_exists($dbColumn, $this->attributes)) {
            return true;
        }

        
        if (array_key_exists($dbColumn, $this->original) && ! array_key_exists($dbColumn, $this->attributes)) {
            return true;
        }

        $originalValue = $this->original[$dbColumn];
        $currentValue  = $this->attributes[$dbColumn];

        
        if (is_string($originalValue) && (is_object($currentValue) || is_array($currentValue))) {
            return $originalValue !== json_encode($this->normalizeValue($currentValue), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        
        return $originalValue !== $currentValue;
    }

    
    private function containsOnlyEntities(array $data): bool
    {
        if ($data === []) {
            return false;
        }

        foreach ($data as $item) {
            if (! $item instanceof self) {
                return false;
            }
        }

        return true;
    }

    
    private function normalizeValue(mixed $data): mixed
    {
        if (is_array($data)) {
            $normalized = [];

            foreach ($data as $key => $value) {
                $normalized[$key] = $this->normalizeValue($value);
            }

            return $normalized;
        }

        if (is_object($data)) {
            
            if ($data instanceof self) {
                $objectData = $data->toRawArray(false, true);
            } elseif ($data instanceof JsonSerializable) {
                $objectData = $data->jsonSerialize();
            } elseif (method_exists($data, 'toArray')) {
                $objectData = $data->toArray();
            } elseif ($data instanceof Traversable) {
                $objectData = iterator_to_array($data);
            } elseif ($data instanceof DateTimeInterface) {
                return [
                    '__class'    => $data::class,
                    '__datetime' => $data->format(DATE_RFC3339_EXTENDED),
                ];
            } elseif ($data instanceof UnitEnum) {
                return [
                    '__class' => $data::class,
                    '__enum'  => $data instanceof BackedEnum ? $data->value : $data->name,
                ];
            } else {
                $objectData = get_object_vars($data);

                
                
                if ($objectData === [] && method_exists($data, '__toString')) {
                    return [
                        '__class'  => $data::class,
                        '__string' => (string) $data,
                    ];
                }
            }

            return [
                '__class' => $data::class,
                '__data'  => $this->normalizeValue($objectData),
            ];
        }

        
        return $data;
    }

    
    public function injectRawData(array $data)
    {
        $this->attributes = $data;

        $this->syncOriginal();

        return $this;
    }

    
    protected function mapProperty(string $key)
    {
        if ($this->datamap === []) {
            return $key;
        }

        if (array_key_exists($key, $this->datamap) && $this->datamap[$key] !== '') {
            return $this->datamap[$key];
        }

        return $key;
    }

    
    protected function mutateDate($value)
    {
        return DatetimeCast::get($value);
    }

    
    protected function castAs($value, string $attribute, string $method = 'get')
    {
        if ($this->dataCaster() instanceof DataCaster) {
            return $this->dataCaster
                
                ->setTypes($this->casts)
                ->castAs($value, $attribute, $method);
        }

        return $value;
    }

    
    protected function dataCaster(): ?DataCaster
    {
        if ($this->casts === []) {
            $this->dataCaster = null;

            return null;
        }

        if (! $this->dataCaster instanceof DataCaster) {
            $this->dataCaster = new DataCaster(
                array_merge($this->defaultCastHandlers, $this->castHandlers),
                null,
                null,
                false,
            );
        }

        return $this->dataCaster;
    }

    
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    
    public function cast(?bool $cast = null)
    {
        if ($cast === null) {
            return $this->_cast;
        }

        $this->_cast = $cast;

        return $this;
    }

    
    public function __set(string $key, $value = null)
    {
        $dbColumn = $this->mapProperty($key);

        
        if (in_array($dbColumn, $this->dates, true)) {
            $value = $this->mutateDate($value);
        }

        $value = $this->castAs($value, $dbColumn, 'set');

        
        
        
        $method = 'set' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $dbColumn)));

        
        if (method_exists($this, '_' . $method)) {
            $this->{'_' . $method}($value);

            return;
        }

        
        if (method_exists($this, $method)) {
            $this->{$method}($value);

            return;
        }

        
        
        
        
        $this->attributes[$dbColumn] = $value;
    }

    
    public function __get(string $key)
    {
        $dbColumn = $this->mapProperty($key);

        $result = null;

        
        $method = 'get' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $dbColumn)));

        
        
        if (method_exists($this, '_' . $method)) {
            
            $result = $this->{'_' . $method}();
        } elseif (method_exists($this, $method)) {
            
            $result = $this->{$method}();
        }

        
        
        elseif (array_key_exists($dbColumn, $this->attributes)) {
            $result = $this->attributes[$dbColumn];
        }

        
        if (in_array($dbColumn, $this->dates, true)) {
            $result = $this->mutateDate($result);
        }
        
        elseif ($this->_cast) {
            $result = $this->castAs($result, $dbColumn);
        }

        return $result;
    }

    
    public function __isset(string $key): bool
    {
        if ($this->isMappedDbColumn($key)) {
            return false;
        }

        $dbColumn = $this->mapProperty($key);

        $method = 'get' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $dbColumn)));

        if (method_exists($this, $method)) {
            return true;
        }

        return isset($this->attributes[$dbColumn]);
    }

    
    public function __unset(string $key): void
    {
        if ($this->isMappedDbColumn($key)) {
            return;
        }

        $dbColumn = $this->mapProperty($key);

        unset($this->attributes[$dbColumn]);
    }

    
    protected function isMappedDbColumn(string $key): bool
    {
        $dbColumn = $this->mapProperty($key);

        
        if ($key !== $dbColumn) {
            return false;
        }

        return $this->hasMappedProperty($key);
    }

    
    protected function hasMappedProperty(string $key): bool
    {
        $property = array_search($key, $this->datamap, true);

        return $property !== false;
    }
}
