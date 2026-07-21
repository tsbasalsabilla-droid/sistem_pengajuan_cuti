<?php



namespace CodeIgniter\Config;

use CodeIgniter\Autoloader\FileLocatorInterface;
use CodeIgniter\Exceptions\ConfigException;
use CodeIgniter\Exceptions\RuntimeException;
use Config\Encryption;
use Config\Modules;
use ReflectionClass;
use ReflectionException;


class BaseConfig
{
    
    public static $registrars = [];

    
    public static bool $override = true;

    
    protected static $didDiscovery = false;

    
    protected static bool $discovering = false;

    
    protected static string $registrarFile = '';

    
    protected static $moduleConfig;

    public static function __set_state(array $array)
    {
        static::$override = false;
        $obj              = new static();
        static::$override = true;

        $properties = array_keys(get_object_vars($obj));

        foreach ($properties as $property) {
            $obj->{$property} = $array[$property];
        }

        return $obj;
    }

    
    public static function setModules(Modules $modules): void
    {
        static::$moduleConfig = $modules;
    }

    
    public static function reset(): void
    {
        static::$registrars   = [];
        static::$override     = true;
        static::$didDiscovery = false;
        static::$moduleConfig = null;
    }

    
    public function __construct()
    {
        static::$moduleConfig ??= new Modules();

        if (! static::$override) {
            return;
        }

        $this->registerProperties();

        $properties  = array_keys(get_object_vars($this));
        $prefix      = static::class;
        $slashAt     = strrpos($prefix, '\\');
        $shortPrefix = strtolower(substr($prefix, $slashAt === false ? 0 : $slashAt + 1));

        foreach ($properties as $property) {
            $this->initEnvValue($this->{$property}, $property, $prefix, $shortPrefix);

            if ($this instanceof Encryption) {
                if ($property === 'key') {
                    $this->{$property} = $this->parseEncryptionKey($this->{$property});
                } elseif ($property === 'previousKeys') {
                    $keysArray  = is_string($this->{$property}) ? array_map(trim(...), explode(',', $this->{$property})) : $this->{$property};
                    $parsedKeys = [];

                    foreach ($keysArray as $key) {
                        $parsedKeys[] = $this->parseEncryptionKey($key);
                    }

                    $this->{$property} = $parsedKeys;
                }
            }
        }
    }

    
    protected function parseEncryptionKey(string $key): string
    {
        if (str_starts_with($key, 'hex2bin:')) {
            return hex2bin(substr($key, 8));
        }

        if (str_starts_with($key, 'base64:')) {
            return base64_decode(substr($key, 7), true);
        }

        return $key;
    }

    
    protected function initEnvValue(&$property, string $name, string $prefix, string $shortPrefix)
    {
        if (is_array($property)) {
            foreach (array_keys($property) as $key) {
                $this->initEnvValue($property[$key], "{$name}.{$key}", $prefix, $shortPrefix);
            }
        } elseif (($value = $this->getEnvValue($name, $prefix, $shortPrefix)) !== false && $value !== null) {
            if ($value === 'false') {
                $value = false;
            } elseif ($value === 'true') {
                $value = true;
            }
            if (is_bool($value)) {
                $property = $value;

                return;
            }

            $value = trim($value, '\'"');

            if (is_int($property)) {
                $value = (int) $value;
            } elseif (is_float($property)) {
                $value = (float) $value;
            }

            
            
            
            $property = $value;
        }
    }

    
    protected function getEnvValue(string $property, string $prefix, string $shortPrefix)
    {
        $shortPrefix        = ltrim($shortPrefix, '\\');
        $underscoreProperty = str_replace('.', '_', $property);

        switch (true) {
            case array_key_exists("{$shortPrefix}.{$property}", $_ENV):
                return $_ENV["{$shortPrefix}.{$property}"];

            case array_key_exists("{$shortPrefix}_{$underscoreProperty}", $_ENV):
                return $_ENV["{$shortPrefix}_{$underscoreProperty}"];

            case array_key_exists("{$shortPrefix}.{$property}", $_SERVER):
                return $_SERVER["{$shortPrefix}.{$property}"];

            case array_key_exists("{$shortPrefix}_{$underscoreProperty}", $_SERVER):
                return $_SERVER["{$shortPrefix}_{$underscoreProperty}"];

            case array_key_exists("{$prefix}.{$property}", $_ENV):
                return $_ENV["{$prefix}.{$property}"];

            case array_key_exists("{$prefix}_{$underscoreProperty}", $_ENV):
                return $_ENV["{$prefix}_{$underscoreProperty}"];

            case array_key_exists("{$prefix}.{$property}", $_SERVER):
                return $_SERVER["{$prefix}.{$property}"];

            case array_key_exists("{$prefix}_{$underscoreProperty}", $_SERVER):
                return $_SERVER["{$prefix}_{$underscoreProperty}"];

            default:
                $value = getenv("{$shortPrefix}.{$property}");
                $value = $value === false ? getenv("{$shortPrefix}_{$underscoreProperty}") : $value;
                $value = $value === false ? getenv("{$prefix}.{$property}") : $value;
                $value = $value === false ? getenv("{$prefix}_{$underscoreProperty}") : $value;

                return $value === false ? null : $value;
        }
    }

    
    protected function registerProperties()
    {
        if (! static::$moduleConfig->shouldDiscover('registrars')) {
            return;
        }

        if (! static::$didDiscovery) {
            
            if (static::$discovering) {
                throw new ConfigException(
                    'During Auto-Discovery of Registrars,'
                    . ' "' . static::class . '" executes Auto-Discovery again.'
                    . ' "' . clean_path(static::$registrarFile) . '" seems to have bad code.',
                );
            }

            static::$discovering = true;

            
            $locator         = service('locator');
            $registrarsFiles = $locator->search('Config/Registrar.php');

            foreach ($registrarsFiles as $file) {
                
                static::$registrarFile = $file;

                $className = $locator->findQualifiedNameFromPath($file);

                if ($className === false) {
                    continue;
                }

                static::$registrars[] = new $className();
            }

            static::$didDiscovery = true;
            static::$discovering  = false;
        }

        $shortName = (new ReflectionClass($this))->getShortName();

        
        foreach (static::$registrars as $callable) {
            
            if (! method_exists($callable, $shortName)) {
                continue; 
            }

            $properties = $callable::$shortName();

            if (! is_array($properties)) {
                throw new RuntimeException('Registrars must return an array of properties and their values.');
            }

            foreach ($properties as $property => $value) {
                if (isset($this->{$property}) && is_array($this->{$property}) && is_array($value)) {
                    $this->{$property} = array_merge($this->{$property}, $value);
                } else {
                    $this->{$property} = $value;
                }
            }
        }
    }
}
