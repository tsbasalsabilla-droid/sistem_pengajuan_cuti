<?php



use CodeIgniter\Boot;
use CodeIgniter\Config\Factories;
use CodeIgniter\Config\Services;
use CodeIgniter\Database\Config as DatabaseConfig;
use CodeIgniter\Events\Events;
use Config\Paths;
use Config\WorkerMode;



$minPhpVersion = '8.2';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    http_response_code(503);
    exit("PHP {$minPhpVersion}+ required. Current: " . PHP_VERSION);
}



define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}




require FCPATH . '../app/Config/Paths.php';

$paths = new Paths();

require $paths->systemDirectory . '/Boot.php';


$app = Boot::bootWorker($paths);


ignore_user_abort(true);


$workerConfig = config('WorkerMode');



$handler = static function () use ($app, $workerConfig) {
    
    DatabaseConfig::reconnectForWorkerMode();

    
    Services::reconnectCacheForWorkerMode();

    
    $app->resetForWorkerMode();

    
    service('superglobals')
        ->setServerArray($_SERVER)
        ->setGetArray($_GET)
        ->setPostArray($_POST)
        ->setCookieArray($_COOKIE)
        ->setFilesArray($_FILES)
        ->setRequestArray($_REQUEST);

    try {
        $app->run();
    } catch (Throwable $e) {
        Services::exceptions()->exceptionHandler($e);
    }

    if ($workerConfig->forceGarbageCollection) {
        
        gc_collect_cycles();
    }
};



while (frankenphp_handle_request($handler)) {
    
    if (Services::has('session')) {
        Services::session()->close();
    }

    
    DatabaseConfig::cleanupForWorkerMode();

    
    Factories::reset();

    
    Services::resetForWorkerMode($workerConfig);

    
    Events::cleanupForWorkerMode($workerConfig->resetEventListeners);

    if (CI_DEBUG) {
        Services::toolbar()->reset();
    }
}
