<?php

declare(strict_types=1);



namespace CodeIgniter\Autoloader;

use CodeIgniter\Exceptions\ConfigException;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Exceptions\RuntimeException;
use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Config\Autoload;
use Config\Kint as KintConfig;
use Config\Modules;
use Kint\Kint;
use Kint\Renderer\CliRenderer;
use Kint\Renderer\RichRenderer;


class Autoloader
{
    
    protected $prefixes = [];

    
    protected $classmap = [];

    
    protected $files = [];

    
    protected $helpers = ['url'];

    
    public function initialize(Autoload $config, Modules $modules)
    {
        $this->prefixes = [];
        $this->classmap = [];
        $this->files    = [];

        
        
        if ($config->psr4 === [] && $config->classmap === []) {
            throw new InvalidArgumentException('Config array must contain either the \'psr4\' key or the \'classmap\' key.');
        }

        if ($config->psr4 !== []) {
            $this->addNamespace($config->psr4);
        }

        if ($config->classmap !== []) {
            $this->classmap = $config->classmap;
        }

        if ($config->files !== []) {
            $this->files = $config->files;
        }

        if ($config->helpers !== []) {
            $this->helpers = [...$this->helpers, ...$config->helpers];
        }

        if (is_file(COMPOSER_PATH)) {
            $this->loadComposerAutoloader($modules);
        }

        return $this;
    }

    private function loadComposerAutoloader(Modules $modules): void
    {
        
        
        if (! defined('VENDORPATH')) {
            define('VENDORPATH', dirname(COMPOSER_PATH) . DIRECTORY_SEPARATOR);
        }

        
        $composer = include COMPOSER_PATH;

        
        if ($modules->discoverInComposer) {
            $composerPackages = $modules->composerPackages;
            $this->loadComposerNamespaces($composer, $composerPackages ?? []);
        }

        unset($composer);
    }

    
    public function register()
    {
        spl_autoload_register($this->loadClassmap(...), true);
        spl_autoload_register($this->loadClass(...), true);

        foreach ($this->files as $file) {
            $this->includeFile($file);
        }
    }

    
    public function unregister(): void
    {
        spl_autoload_unregister($this->loadClass(...));
        spl_autoload_unregister($this->loadClassmap(...));
    }

    
    public function addNamespace($namespace, ?string $path = null)
    {
        if (is_array($namespace)) {
            foreach ($namespace as $prefix => $namespacedPath) {
                $prefix = trim($prefix, '\\');

                if (is_array($namespacedPath)) {
                    foreach ($namespacedPath as $dir) {
                        $this->prefixes[$prefix][] = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR;
                    }

                    continue;
                }

                $this->prefixes[$prefix][] = rtrim($namespacedPath, '\\/') . DIRECTORY_SEPARATOR;
            }
        } else {
            $this->prefixes[trim($namespace, '\\')][] = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
        }

        return $this;
    }

    
    public function getNamespace(?string $prefix = null)
    {
        if ($prefix === null) {
            return $this->prefixes;
        }

        return $this->prefixes[trim($prefix, '\\')] ?? [];
    }

    
    public function removeNamespace(string $namespace)
    {
        if (isset($this->prefixes[trim($namespace, '\\')])) {
            unset($this->prefixes[trim($namespace, '\\')]);
        }

        return $this;
    }

    
    public function loadClassmap(string $class): void
    {
        $file = $this->classmap[$class] ?? '';

        if (is_string($file) && $file !== '') {
            $this->includeFile($file);
        }
    }

    
    public function loadClass(string $class): void
    {
        $this->loadInNamespace($class);
    }

    
    protected function loadInNamespace(string $class)
    {
        if (! str_contains($class, '\\')) {
            return false;
        }

        foreach ($this->prefixes as $namespace => $directories) {
            if (str_starts_with($class, $namespace)) {
                $relativeClassPath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($namespace)));

                foreach ($directories as $directory) {
                    $directory = rtrim($directory, '\\/');

                    $filePath = $directory . $relativeClassPath . '.php';
                    $filename = $this->includeFile($filePath);

                    if ($filename !== false) {
                        return $filename;
                    }
                }
            }
        }

        return false;
    }

    
    protected function includeFile(string $file)
    {
        if (is_file($file)) {
            include_once $file;

            return $file;
        }

        return false;
    }

    
    public function sanitizeFilename(string $filename): string
    {
        
        
        
        
        $result = preg_match_all('/[^0-9\p{L}\s\/\-_.:\\\\]/u', $filename, $matches);

        if ($result > 0) {
            $chars = implode('', $matches[0]);

            throw new InvalidArgumentException(
                'The file path contains special characters "' . $chars
                . '" that are not allowed: "' . $filename . '"',
            );
        }
        if ($result === false) {
            $message = preg_last_error_msg();

            throw new RuntimeException($message . '. filename: "' . $filename . '"');
        }

        
        $cleanFilename = trim($filename, '.-_');

        if ($filename !== $cleanFilename) {
            throw new InvalidArgumentException('The characters ".-_" are not allowed in filename edges: "' . $filename . '"');
        }

        return $cleanFilename;
    }

    
    private function loadComposerNamespaces(ClassLoader $composer, array $composerPackages): void
    {
        $namespacePaths = $composer->getPrefixesPsr4();

        
        $duplicatedNamespaces = ['CodeIgniter', APP_NAMESPACE, 'Config'];

        foreach ($duplicatedNamespaces as $ns) {
            if (isset($namespacePaths[$ns . '\\'])) {
                unset($namespacePaths[$ns . '\\']);
            }
        }

        if (! method_exists(InstalledVersions::class, 'getAllRawData')) { 
            throw new RuntimeException(
                'Your Composer version is too old.'
                . ' Please update Composer (run `composer self-update`) to v2.0.14 or later'
                . ' and remove your vendor/ directory, and run `composer update`.',
            );
        }
        
        $allData     = InstalledVersions::getAllRawData();
        $packageList = [];

        foreach ($allData as $list) {
            $packageList = array_merge($packageList, $list['versions']);
        }

        
        $only    = $composerPackages['only'] ?? [];
        $exclude = $composerPackages['exclude'] ?? [];
        if ($only !== [] && $exclude !== []) {
            throw new ConfigException('Cannot use "only" and "exclude" at the same time in "Config\Modules::$composerPackages".');
        }

        
        $installPaths = [];
        if ($only !== []) {
            foreach ($packageList as $packageName => $data) {
                if (in_array($packageName, $only, true) && isset($data['install_path'])) {
                    $installPaths[] = $data['install_path'];
                }
            }
        } else {
            foreach ($packageList as $packageName => $data) {
                if (! in_array($packageName, $exclude, true) && isset($data['install_path'])) {
                    $installPaths[] = $data['install_path'];
                }
            }
        }

        $newPaths = [];

        foreach ($namespacePaths as $namespace => $srcPaths) {
            $add = false;

            foreach ($srcPaths as $path) {
                foreach ($installPaths as $installPath) {
                    if (str_starts_with($path, $installPath)) {
                        $add = true;
                        break 2;
                    }
                }
            }

            if ($add) {
                
                $newPaths[rtrim($namespace, '\\ ')] = $srcPaths;
            }
        }

        $this->addNamespace($newPaths);
    }

    
    protected function discoverComposerNamespaces()
    {
        if (! is_file(COMPOSER_PATH)) {
            return;
        }

        
        $composer = include COMPOSER_PATH;
        $paths    = $composer->getPrefixesPsr4();
        $classes  = $composer->getClassMap();

        unset($composer);

        
        if (isset($paths['CodeIgniter\\'])) {
            unset($paths['CodeIgniter\\']);
        }

        $newPaths = [];

        foreach ($paths as $key => $value) {
            
            $newPaths[rtrim($key, '\\ ')] = $value;
        }

        $this->prefixes = array_merge($this->prefixes, $newPaths);
        $this->classmap = array_merge($this->classmap, $classes);
    }

    
    public function loadHelpers(): void
    {
        helper($this->helpers);
    }

    
    public function initializeKint(bool $debug = false): void
    {
        if ($debug) {
            $this->autoloadKint();
            $this->configureKint();
        } elseif (class_exists(Kint::class)) {
            
            Kint::$enabled_mode = false;
        }

        helper('kint');
    }

    private function autoloadKint(): void
    {
        
        if (! defined('KINT_DIR')) {
            spl_autoload_register(function ($class): void {
                $class = explode('\\', $class);

                if (array_shift($class) !== 'Kint') {
                    return;
                }

                $file = SYSTEMPATH . 'ThirdParty/Kint/' . implode('/', $class) . '.php';

                if (is_file($file)) {
                    require_once $file;
                }
            });

            require_once SYSTEMPATH . 'ThirdParty/Kint/init.php';
        }
    }

    private function configureKint(): void
    {
        $config = new KintConfig();

        Kint::$depth_limit         = $config->maxDepth;
        Kint::$display_called_from = $config->displayCalledFrom;
        Kint::$expanded            = $config->expanded;

        if (isset($config->plugins) && is_array($config->plugins)) {
            Kint::$plugins = $config->plugins;
        }

        $csp = service('csp');
        if ($csp->enabled()) {
            RichRenderer::$js_nonce  = $csp->getScriptNonce();
            RichRenderer::$css_nonce = $csp->getStyleNonce();
        }

        RichRenderer::$theme  = $config->richTheme;
        RichRenderer::$folder = $config->richFolder;

        if (isset($config->richObjectPlugins) && is_array($config->richObjectPlugins)) {
            RichRenderer::$value_plugins = $config->richObjectPlugins;
        }
        if (isset($config->richTabPlugins) && is_array($config->richTabPlugins)) {
            RichRenderer::$tab_plugins = $config->richTabPlugins;
        }

        CliRenderer::$cli_colors         = $config->cliColors;
        CliRenderer::$force_utf8         = $config->cliForceUTF8;
        CliRenderer::$detect_width       = $config->cliDetectWidth;
        CliRenderer::$min_terminal_width = $config->cliMinWidth;
    }
}
