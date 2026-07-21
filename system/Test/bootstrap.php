<?php

declare(strict_types=1);



use CodeIgniter\Boot;
use Config\Paths;

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');




$_SERVER['CI_ENVIRONMENT'] = 'testing';
define('ENVIRONMENT', 'testing');

defined('CI_DEBUG') || define('CI_DEBUG', true);




defined('HOMEPATH') || define('HOMEPATH', realpath(rtrim(getcwd(), '\\/ ')) . DIRECTORY_SEPARATOR);
$source = is_dir(HOMEPATH . 'app')
    ? HOMEPATH
    : (is_dir('vendor/codeigniter4/framework/') ? 'vendor/codeigniter4/framework/' : 'vendor/codeigniter4/codeigniter4/');
defined('CONFIGPATH') || define('CONFIGPATH', realpath($source . 'app/Config') . DIRECTORY_SEPARATOR);
defined('PUBLICPATH') || define('PUBLICPATH', realpath($source . 'public') . DIRECTORY_SEPARATOR);
unset($source);



require CONFIGPATH . 'Paths.php';
$paths = new Paths();


defined('APPPATH')    || define('APPPATH', realpath(rtrim($paths->appDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
defined('ROOTPATH')   || define('ROOTPATH', realpath(APPPATH . '../') . DIRECTORY_SEPARATOR);
defined('SYSTEMPATH') || define('SYSTEMPATH', realpath(rtrim($paths->systemDirectory, '\\/')) . DIRECTORY_SEPARATOR);
defined('WRITEPATH')  || define('WRITEPATH', realpath(rtrim($paths->writableDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
defined('TESTPATH')   || define('TESTPATH', realpath(HOMEPATH . 'tests/') . DIRECTORY_SEPARATOR);

defined('CIPATH') || define('CIPATH', realpath(SYSTEMPATH . '../') . DIRECTORY_SEPARATOR);
defined('FCPATH') || define('FCPATH', realpath(PUBLICPATH) . DIRECTORY_SEPARATOR);

defined('SUPPORTPATH')   || define('SUPPORTPATH', realpath(TESTPATH . '_support/') . DIRECTORY_SEPARATOR);
defined('COMPOSER_PATH') || define('COMPOSER_PATH', (string) realpath(HOMEPATH . 'vendor/autoload.php'));
defined('VENDORPATH')    || define('VENDORPATH', realpath(HOMEPATH . 'vendor') . DIRECTORY_SEPARATOR);




require $paths->systemDirectory . '/Boot.php';
Boot::bootTest($paths);



service('routes')->loadRoutes();
