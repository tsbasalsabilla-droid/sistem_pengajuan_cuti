<?php

declare(strict_types=1);



namespace CodeIgniter\Config;

use CodeIgniter\Autoloader\FileLocatorInterface;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Model;


final class Factories
{
    
    private static array $options = [];

    
    private static array $configOptions = [
        'component'  => 'config',
        'path'       => 'Config',
        'instanceOf' => null,
        'getShared'  => true,
        'preferApp'  => true,
    ];

    
    private static array $aliases = [];

    
    private static array $instances = [];

    
    private static array $updated = [];

    
    public static function define(string $component, string $alias, string $classname): void
    {
        $component = strtolower($component);

        if (isset(self::$aliases[$component][$alias])) {
            if (self::$aliases[$component][$alias] === $classname) {
                return;
            }

            throw new InvalidArgumentException(
                'Already defined in Factories: ' . $component . ' ' . $alias . ' -> ' . self::$aliases[$component][$alias],
            );
        }

        if (! class_exists($classname)) {
            throw new InvalidArgumentException('No such class: ' . $classname);
        }

        
        
        self::getOptions($component);

        self::$aliases[$component][$alias] = $classname;
        self::$updated[$component]         = true;
    }

    
    public static function __callStatic(string $component, array $arguments)
    {
        $component = strtolower($component);

        
        $alias   = trim(array_shift($arguments), '\\ ');
        $options = array_shift($arguments) ?? [];

        
        $options = array_merge(self::getOptions($component), $options);

        if (! $options['getShared']) {
            if (isset(self::$aliases[$options['component']][$alias])) {
                $class = self::$aliases[$options['component']][$alias];

                return new $class(...$arguments);
            }

            
            $class = self::locateClass($options, $alias);
            if ($class !== null) {
                return new $class(...$arguments);
            }

            return null;
        }

        
        $instance = self::getDefinedInstance($options, $alias, $arguments);
        if ($instance !== null) {
            return $instance;
        }

        
        if (($class = self::locateClass($options, $alias)) === null) {
            return null;
        }

        self::createInstance($options['component'], $class, $arguments);
        self::setAlias($options['component'], $alias, $class);

        return self::$instances[$options['component']][$class];
    }

    
    public static function get(string $component, string $alias): ?object
    {
        if (isset(self::$aliases[$component][$alias])) {
            $class = self::$aliases[$component][$alias];

            if (isset(self::$instances[$component][$class])) {
                return self::$instances[$component][$class];
            }
        }

        return self::__callStatic($component, [$alias]);
    }

    
    private static function getDefinedInstance(array $options, string $alias, array $arguments)
    {
        
        if (isset(self::$aliases[$options['component']][$alias])) {
            $class = self::$aliases[$options['component']][$alias];

            
            if (self::verifyInstanceOf($options, $class)) {
                
                if (isset(self::$instances[$options['component']][$class])) {
                    return self::$instances[$options['component']][$class];
                }

                self::createInstance($options['component'], $class, $arguments);

                return self::$instances[$options['component']][$class];
            }
        }

        
        if (($class = self::locateClass($options, $alias)) === null) {
            return null;
        }

        
        if (isset(self::$instances[$options['component']][$class])) {
            self::setAlias($options['component'], $alias, $class);

            return self::$instances[$options['component']][$class];
        }

        return null;
    }

    
    private static function createInstance(string $component, string $class, array $arguments): void
    {
        self::$instances[$component][$class] = new $class(...$arguments);
        self::$updated[$component]           = true;
    }

    
    private static function setAlias(string $component, string $alias, string $class): void
    {
        self::$aliases[$component][$alias] = $class;
        self::$updated[$component]         = true;

        
        if (! isset(self::$aliases[$component][$class]) && ! self::isNamespaced($alias)) {
            self::$aliases[$component][$class] = $class;
        }
    }

    
    private static function isConfig(string $component): bool
    {
        return $component === 'config';
    }

    
    private static function locateClass(array $options, string $alias): ?string
    {
        
        if (
            class_exists($alias, false)
            && self::verifyPreferApp($options, $alias)
            && self::verifyInstanceOf($options, $alias)
        ) {
            return $alias;
        }

        
        $basename = self::getBasename($alias);
        $appname  = self::isConfig($options['component'])
            ? 'Config\\' . $basename
            : rtrim(APP_NAMESPACE, '\\') . '\\' . $options['path'] . '\\' . $basename;

        
        if (
            
            ! self::isNamespaced($alias)
            && $options['preferApp'] && class_exists($appname)
            && self::verifyInstanceOf($options, $alias)
        ) {
            return $appname;
        }

        
        if (class_exists($alias) && self::verifyInstanceOf($options, $alias)) {
            return $alias;
        }

        
        
        $locator = service('locator');

        
        if (self::isNamespaced($alias)) {
            if (! $file = $locator->locateFile($alias, $options['path'])) {
                return null;
            }
            $files = [$file];
        }
        
        
        elseif (($files = $locator->search($options['path'] . DIRECTORY_SEPARATOR . $alias)) === []) {
            return null;
        }

        
        foreach ($files as $file) {
            $class = $locator->findQualifiedNameFromPath($file);

            if ($class !== false && self::verifyInstanceOf($options, $class)) {
                return $class;
            }
        }

        return null;
    }

    
    private static function isNamespaced(string $alias): bool
    {
        return str_contains($alias, '\\');
    }

    
    private static function verifyPreferApp(array $options, string $alias): bool
    {
        
        if (! $options['preferApp']) {
            return true;
        }

        
        if (self::isConfig($options['component'])) {
            return str_starts_with($alias, 'Config');
        }

        return str_starts_with($alias, APP_NAMESPACE);
    }

    
    private static function verifyInstanceOf(array $options, string $alias): bool
    {
        
        if (! $options['instanceOf']) {
            return true;
        }

        return is_a($alias, $options['instanceOf'], true);
    }

    
    public static function getOptions(string $component): array
    {
        $component = strtolower($component);

        
        if (isset(self::$options[$component])) {
            return self::$options[$component];
        }

        $values = self::isConfig($component)
            
            ? self::$configOptions
            
            : config('Factory')->{$component} ?? [];

        
        
        return self::setOptions($component, $values);
    }

    
    public static function setOptions(string $component, array $values): array
    {
        $component = strtolower($component);

        
        $values['component'] = strtolower($values['component'] ?? $component);

        
        self::reset($values['component']);

        
        $values['path'] = trim($values['path'] ?? ucfirst($values['component']), '\\ ');

        
        $values = array_merge(Factory::$default, $values);

        
        self::$options[$component]           = $values;
        self::$options[$values['component']] = $values;

        return $values;
    }

    
    public static function reset(?string $component = null)
    {
        if ($component !== null) {
            unset(
                self::$options[$component],
                self::$aliases[$component],
                self::$instances[$component],
                self::$updated[$component],
            );

            return;
        }

        self::$options   = [];
        self::$aliases   = [];
        self::$instances = [];
        self::$updated   = [];
    }

    
    public static function injectMock(string $component, string $alias, object $instance)
    {
        $component = strtolower($component);

        
        self::getOptions($component);

        $class = $instance::class;

        self::$instances[$component][$class] = $instance;
        self::$aliases[$component][$alias]   = $class;

        if (self::isConfig($component)) {
            if (self::isNamespaced($alias)) {
                self::$aliases[$component][self::getBasename($alias)] = $class;
            } else {
                self::$aliases[$component]['Config\\' . $alias] = $class;
            }
        }
    }

    
    public static function getBasename(string $alias): string
    {
        
        if ($basename = strrchr($alias, '\\')) {
            return substr($basename, 1);
        }

        return $alias;
    }

    
    public static function getComponentInstances(string $component): array
    {
        if (! isset(self::$aliases[$component])) {
            return [
                'options'   => [],
                'aliases'   => [],
                'instances' => [],
            ];
        }

        return [
            'options'   => self::$options[$component],
            'aliases'   => self::$aliases[$component],
            'instances' => self::$instances[$component],
        ];
    }

    
    public static function setComponentInstances(string $component, array $data): void
    {
        self::$options[$component]   = $data['options'];
        self::$aliases[$component]   = $data['aliases'];
        self::$instances[$component] = $data['instances'];

        unset(self::$updated[$component]);
    }

    
    public static function isUpdated(string $component): bool
    {
        return isset(self::$updated[$component]);
    }
}
