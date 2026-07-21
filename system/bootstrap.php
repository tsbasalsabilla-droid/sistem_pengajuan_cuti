<?php

declare(strict_types=1);





use CodeIgniter\Exceptions\FrameworkException;
use Config\Autoload;
use Config\Modules;
use Config\Paths;
use Config\Services;

header('HTTP/1.1 503 Service Unavailable.', true, 503);

$message = 'This "system/bootstrap.php" is no longer used. If you are seeing this error message,
the upgrade is not complete. Please refer to the upgrade guide and complete the upgrade.
See https://codeigniter4.github.io/userguide/installation/upgrade_450.html' . PHP_EOL;
echo $message;






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
    define('WRITEPATH', realpath(rtrim($paths->writableDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
}


if (! defined('TESTPATH')) {
    define('TESTPATH', realpath(rtrim($paths->testsDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
}



if (! defined('APP_NAMESPACE')) {
    require_once APPPATH . 'Config/Constants.php';
}




if (is_file(APPPATH . 'Common.php')) {
    require_once APPPATH . 'Common.php';
}


require_once SYSTEMPATH . 'Common.php';



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
Services::autoloader()->loadHelpers();



Services::exceptions()->initialize();




if (! is_file(COMPOSER_PATH)) {
    $missingExtensions = [];

    foreach ([
        'intl',
        'json',
        'mbstring',
    ] as $extension) {
        if (! extension_loaded($extension)) {
            $missingExtensions[] = $extension;
        }
    }

    if ($missingExtensions !== []) {
        throw FrameworkException::forMissingExtension(implode(', ', $missingExtensions));
    }

    unset($missingExtensions);
}



Services::autoloader()->initializeKint(CI_DEBUG);

exit(1);
