<?php

declare(strict_types=1);



namespace CodeIgniter;

use CodeIgniter\Cache\FactoriesCache;
use CodeIgniter\CLI\Console;
use CodeIgniter\Config\DotEnv;
use Config\App;
use Config\Autoload;
use Config\Modules;
use Config\Optimize;
use Config\Paths;
use Config\Services;


class Boot
{
    
    public static function bootWeb(Paths $paths): int
    {
        static::definePathConstants($paths);
        if (! defined('APP_NAMESPACE')) {
            static::loadConstants();
        }
        static::checkMissingExtensions();

        static::loadDotEnv($paths);
        static::defineEnvironment();
        static::loadEnvironmentBootstrap($paths);

        static::loadCommonFunctions();
        static::loadAutoloader();
        static::setExceptionHandler();
        static::initializeKint();

        $configCacheEnabled = class_exists(Optimize::class)
            && (new Optimize())->configCacheEnabled;
        if ($configCacheEnabled) {
            $factoriesCache = static::loadConfigCache();
        }

        static::autoloadHelpers();

        $app = static::initializeCodeIgniter();
        static::runCodeIgniter($app);

        if ($configCacheEnabled) {
            static::saveConfigCache($factoriesCache);
        }

        
        
        return EXIT_SUCCESS;
    }

    
    public static function bootWorker(Paths $paths): CodeIgniter
    {
        static::definePathConstants($paths);
        if (! defined('APP_NAMESPACE')) {
            static::loadConstants();
        }
        static::checkMissingExtensions();

        static::loadDotEnv($paths);
        static::defineEnvironment();
        static::loadEnvironmentBootstrap($paths);

        static::loadCommonFunctions();
        static::loadAutoloader();
        static::setExceptionHandler();
        static::initializeKint();

        static::checkOptimizationsForWorker();

        static::autoloadHelpers();

        return Boot::initializeCodeIgniter();
    }

    
    public static function bootConsole(Paths $paths): void
    {
        static::definePathConstants($paths);
        static::loadConstants();
        static::checkMissingExtensions();

        static::loadDotEnv($paths);
        static::loadEnvironmentBootstrap($paths);

        static::loadCommonFunctions();
        static::loadAutoloader();
        static::setExceptionHandler();
        static::initializeKint();
        static::autoloadHelpers();

        
        Services::createRequest(new App(), true);
        service('routes')->loadRoutes();
    }

    
    public static function bootSpark(Paths $paths): int
    {
        static::definePathConstants($paths);
        if (! defined('APP_NAMESPACE')) {
            static::loadConstants();
        }
        static::checkMissingExtensions();

        static::loadDotEnv($paths);
        static::defineEnvironment();
        static::loadEnvironmentBootstrap($paths);

        static::loadCommonFunctions();
        static::loadAutoloader();
        static::setExceptionHandler();
        static::initializeKint();
        static::autoloadHelpers();

        static::initializeCodeIgniter();
        $console = static::initializeConsole();

        return static::runCommand($console);
    }

    
    public static function bootTest(Paths $paths): void
    {
        static::loadConstants();
        static::checkMissingExtensions();

        static::loadDotEnv($paths);
        static::loadEnvironmentBootstrap($paths, false);

        static::loadCommonFunctionsMock();
        static::loadCommonFunctions();

        static::loadAutoloader();
        static::setExceptionHandler();
        static::initializeKint();
        static::autoloadHelpers();
    }

    
    public static function preload(Paths $paths): void
    {
        static::definePathConstants($paths);
        static::loadConstants();
        static::defineEnvironment();
        static::loadEnvironmentBootstrap($paths, false);

        static::loadAutoloader();
    }

    
    protected static function loadDotEnv(Paths $paths): void
    {
        require_once $paths->systemDirectory . '/Config/DotEnv.php';
        $envDirectory = $paths->envDirectory ?? $paths->appDirectory . '/../';
        (new DotEnv($envDirectory))->load();
    }

    protected static function defineEnvironment(): void
    {
        if (! defined('ENVIRONMENT')) {
            
            $env = $_ENV['CI_ENVIRONMENT'] ?? $_SERVER['CI_ENVIRONMENT']
                ?? getenv('CI_ENVIRONMENT')
                ?: 'production';

            define('ENVIRONMENT', $env);
        }
    }

    protected static function loadEnvironmentBootstrap(Paths $paths, bool $exit = true): void
    {
        if (is_file($paths->appDirectory . '/Config/Boot/' . ENVIRONMENT . '.php')) {
            require_once $paths->appDirectory . '/Config/Boot/' . ENVIRONMENT . '.php';

            return;
        }

        if ($exit) {
            header('HTTP/1.1 503 Service Unavailable.', true, 503);
            echo 'The application environment is not set correctly.';

            exit(EXIT_ERROR);
        }
    }

    
    protected static function definePathConstants(Paths $paths): void
    {
        
        if (! defined('APPPATH')) {
            define('APPPATH', realpath(rtrim($paths->appDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }

        
        if (! defined('ROOTPATH')) {
            define('ROOTPATH', realpath(APPPATH . '../') . DIRECTORY_SEPARATOR);
        }

        
        if (! defined('SYSTEMPATH')) {
            define('SYSTEMPATH', realpath(rtrim($paths->systemDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }

        
        if (! defined('WRITEPATH')) {
            $writePath = realpath(rtrim($paths->writableDirectory, '\\/ '));

            if ($writePath === false) {
                header('HTTP/1.1 503 Service Unavailable.', true, 503);
                echo 'The WRITEPATH is not set correctly.';

                
                exit(1);
            }
            define('WRITEPATH', $writePath . DIRECTORY_SEPARATOR);
        }

        
        if (! defined('TESTPATH')) {
            define('TESTPATH', realpath(rtrim($paths->testsDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
        }
    }

    protected static function loadConstants(): void
    {
        require_once APPPATH . 'Config/Constants.php';
    }

    protected static function loadCommonFunctions(): void
    {
        
        if (is_file(APPPATH . 'Common.php')) {
            require_once APPPATH . 'Common.php';
        }

        
        require_once SYSTEMPATH . 'Common.php';
    }

    protected static function loadCommonFunctionsMock(): void
    {
        require_once SYSTEMPATH . 'Test/Mock/MockCommon.php';
    }

    
    protected static function loadAutoloader(): void
    {
        if (! class_exists(Autoload::class, false)) {
            require_once SYSTEMPATH . 'Config/AutoloadConfig.php';
            require_once APPPATH . 'Config/Autoload.php';
            require_once SYSTEMPATH . 'Modules/Modules.php';
            require_once APPPATH . 'Config/Modules.php';
        }

        require_once SYSTEMPATH . 'Autoloader/Autoloader.php';
        require_once SYSTEMPATH . 'Config/BaseService.php';
        require_once SYSTEMPATH . 'Config/Services.php';
        require_once APPPATH . 'Config/Services.php';

        
        Services::autoloader()->initialize(new Autoload(), new Modules())->register();
    }

    protected static function autoloadHelpers(): void
    {
        service('autoloader')->loadHelpers();
    }

    protected static function setExceptionHandler(): void
    {
        service('exceptions')->initialize();
    }

    protected static function checkMissingExtensions(): void
    {
        if (is_file(COMPOSER_PATH)) {
            return;
        }

        
        $missingExtensions = [];

        foreach ([
            'intl',
            'mbstring',
        ] as $extension) {
            if (! extension_loaded($extension)) {
                $missingExtensions[] = $extension;
            }
        }

        if ($missingExtensions === []) {
            return;
        }

        $message = sprintf(
            'The framework needs the following extension(s) installed and loaded: %s.',
            implode(', ', $missingExtensions),
        );

        header('HTTP/1.1 503 Service Unavailable.', true, 503);
        echo $message;

        exit(EXIT_ERROR);
    }

    protected static function checkOptimizationsForWorker(): void
    {
        if (class_exists(Optimize::class)) {
            $optimize = new Optimize();

            if ($optimize->configCacheEnabled || $optimize->locatorCacheEnabled) {
                echo 'Optimization settings (configCacheEnabled, locatorCacheEnabled) '
                    . 'must be disabled in Config\Optimize when running in Worker Mode.';

                exit(EXIT_ERROR);
            }
        }
    }

    protected static function initializeKint(): void
    {
        service('autoloader')->initializeKint(CI_DEBUG);
    }

    protected static function loadConfigCache(): FactoriesCache
    {
        $factoriesCache = new FactoriesCache();
        $factoriesCache->load('config');

        return $factoriesCache;
    }

    
    protected static function initializeCodeIgniter(): CodeIgniter
    {
        $app = service('codeigniter');
        $app->initialize();
        $context = is_cli() ? 'php-cli' : 'web';
        $app->setContext($context);

        return $app;
    }

    
    protected static function runCodeIgniter(CodeIgniter $app): void
    {
        $app->run();
    }

    protected static function saveConfigCache(FactoriesCache $factoriesCache): void
    {
        $factoriesCache->save('config');
    }

    protected static function initializeConsole(): Console
    {
        $console = new Console();

        
        if (is_int($suppress = array_search('--no-header', $_SERVER['argv'], true))) {
            unset($_SERVER['argv'][$suppress]);
            $suppress = true;
        }

        $console->showHeader($suppress);

        return $console;
    }

    protected static function runCommand(Console $console): int
    {
        $exit = $console->run();

        return is_int($exit) ? $exit : EXIT_SUCCESS;
    }
}
