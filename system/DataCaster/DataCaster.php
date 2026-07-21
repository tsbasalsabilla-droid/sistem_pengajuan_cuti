<?php

declare(strict_types=1);



namespace CodeIgniter\DataCaster;

use CodeIgniter\DataCaster\Cast\ArrayCast;
use CodeIgniter\DataCaster\Cast\BooleanCast;
use CodeIgniter\DataCaster\Cast\CastInterface;
use CodeIgniter\DataCaster\Cast\CSVCast;
use CodeIgniter\DataCaster\Cast\DatetimeCast;
use CodeIgniter\DataCaster\Cast\EnumCast;
use CodeIgniter\DataCaster\Cast\FloatCast;
use CodeIgniter\DataCaster\Cast\IntBoolCast;
use CodeIgniter\DataCaster\Cast\IntegerCast;
use CodeIgniter\DataCaster\Cast\JsonCast;
use CodeIgniter\DataCaster\Cast\TimestampCast;
use CodeIgniter\DataCaster\Cast\URICast;
use CodeIgniter\Entity\Cast\CastInterface as EntityCastInterface;
use CodeIgniter\Entity\Exceptions\CastException;
use CodeIgniter\Exceptions\InvalidArgumentException;


final class DataCaster
{
    
    private array $types = [];

    
    private array $castHandlers = [
        'array'     => ArrayCast::class,
        'bool'      => BooleanCast::class,
        'boolean'   => BooleanCast::class,
        'csv'       => CSVCast::class,
        'datetime'  => DatetimeCast::class,
        'enum'      => EnumCast::class,
        'double'    => FloatCast::class,
        'float'     => FloatCast::class,
        'int'       => IntegerCast::class,
        'integer'   => IntegerCast::class,
        'int-bool'  => IntBoolCast::class,
        'json'      => JsonCast::class,
        'timestamp' => TimestampCast::class,
        'uri'       => URICast::class,
    ];

    
    public function __construct(
        ?array $castHandlers = null,
        ?array $types = null,
        private  ?object $helper = null,
        private  bool $strict = true,
    ) {
        $this->castHandlers = array_merge($this->castHandlers, $castHandlers ?? []);

        if ($types !== null) {
            $this->setTypes($types);
        }

        if ($this->strict) {
            foreach ($this->castHandlers as $handler) {
                if (
                    ! is_subclass_of($handler, CastInterface::class)
                    && ! is_subclass_of($handler, EntityCastInterface::class)
                ) {
                    throw new InvalidArgumentException(
                        'Invalid class type. It must implement CastInterface. class: ' . $handler,
                    );
                }
            }
        }
    }

    
    public function setTypes(array $types): static
    {
        $this->types = $types;

        return $this;
    }

    
    public function castAs(mixed $value, string $field, string $method = 'get'): mixed
    {
        if ($method !== 'get' && $method !== 'set') {
            throw CastException::forInvalidMethod($method);
        }

        
        if (! isset($this->types[$field])) {
            return $value;
        }

        $type = $this->types[$field];

        $isNullable = false;

        
        if (str_starts_with($type, '?')) {
            $isNullable = true;

            if ($value === null) {
                return null;
            }

            $type = substr($type, 1);
        } elseif ($value === null) {
            if ($this->strict) {
                $message = 'Field "' . $field . '" is not nullable, but null was passed.';

                throw new InvalidArgumentException($message);
            }
        }

        
        
        $type = ($type === 'json-array') ? 'json[array]' : $type;

        $params = [];

        
        
        if (preg_match('/\A(.+)\[(.+)\]\z/', $type, $matches)) {
            $type   = $matches[1];
            $params = array_map(trim(...), explode(',', $matches[2]));
        }

        if ($isNullable && ! $this->strict) {
            $params[] = 'nullable';
        }

        $type = trim($type, '[]');

        $handlers = $this->castHandlers;

        if (! isset($handlers[$type])) {
            throw new InvalidArgumentException(
                'No such handler for "' . $field . '". Invalid type: ' . $type,
            );
        }

        $handler = $handlers[$type];

        if (
            ! $this->strict
            && ! is_subclass_of($handler, CastInterface::class)
            && ! is_subclass_of($handler, EntityCastInterface::class)
        ) {
            throw CastException::forInvalidInterface($handler);
        }

        return $handler::$method($value, $params, $this->helper);
    }
}
