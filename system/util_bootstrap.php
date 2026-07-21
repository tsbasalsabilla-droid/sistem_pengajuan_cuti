<?php

declare(strict_types=1);



use CodeIgniter\Boot;
use Config\Paths;

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');



$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
defined('CI_DEBUG') || define('CI_DEBUG', true);



defined('HOMEPATH') || define('HOMEPATH', realpath(rtrim(getcwd(), '\\/ ')) . DIRECTORY_SEPARATOR);

$source = match (true) {
    is_dir(HOMEPATH . 'app/')                   => HOMEPATH,
    is_dir('vendor/codeigniter4/framework/')    => 'vendor/codeigniter4/framework/',
    is_dir('vendor/codeigniter4/codeigniter4/') => 'vendor/codeigniter4/codeigniter4/',
    default                                     => throw new RuntimeException('Unable to determine the source directory.'),
};

defined('CONFIGPATH') || define('CONFIGPATH', realpath($source . 'app/Config') . DIRECTORY_SEPARATOR);
defined('PUBLICPATH') || define('PUBLICPATH', realpath($source . 'public') . DIRECTORY_SEPARATOR);
unset($source);

require CONFIGPATH . 'Paths.php';
$paths = new Paths();

defined('CIPATH') || define('CIPATH', realpath($paths->systemDirectory . '/../') . DIRECTORY_SEPARATOR);
defined('FCPATH') || define('FCPATH', PUBLICPATH);

if (is_dir($paths->testsDirectory . '/_support/') && ! defined('SUPPORTPATH')) {
    define('SUPPORTPATH', realpath($paths->testsDirectory . '/_support/') . DIRECTORY_SEPARATOR);
}

if (is_dir(HOMEPATH . 'vendor/')) {
    define('VENDORPATH', realpath(HOMEPATH . 'vendor/') . DIRECTORY_SEPARATOR);
    define('COMPOSER_PATH', (string) realpath(HOMEPATH . 'vendor/autoload.php'));
}



require $paths->systemDirectory . '/Boot.php';
Boot::bootConsole($paths);
