<?php

declare(strict_types=1);



use Kint\Kint;
use Kint\Renderer\AbstractRenderer;
use Kint\Utils;

if (\defined('KINT_DIR')) {
    return;
}

if (\version_compare(PHP_VERSION, '7.4') < 0) {
    throw new Exception('Kint 6 requires PHP 7.4 or higher');
}

\define('KINT_DIR', __DIR__);
\define('KINT_WIN', DIRECTORY_SEPARATOR !== '/');
\define('KINT_PHP80', \version_compare(PHP_VERSION, '8.0') >= 0);
\define('KINT_PHP81', \version_compare(PHP_VERSION, '8.1') >= 0);
\define('KINT_PHP82', \version_compare(PHP_VERSION, '8.2') >= 0);
\define('KINT_PHP83', \version_compare(PHP_VERSION, '8.3') >= 0);
\define('KINT_PHP84', \version_compare(PHP_VERSION, '8.4') >= 0);
\define('KINT_PHP8412', \version_compare(PHP_VERSION, '8.4.12') >= 0);
\define('KINT_PHP85', \version_compare(PHP_VERSION, '8.5') >= 0);


if (\strlen((string) \ini_get('xdebug.file_link_format')) > 0) {
    
    AbstractRenderer::$file_link_format = \ini_get('xdebug.file_link_format');
}
if (isset($_SERVER['DOCUMENT_ROOT']) && false === \strpos($_SERVER['DOCUMENT_ROOT'], "\0")) {
    Utils::$path_aliases = [
        $_SERVER['DOCUMENT_ROOT'] => '<ROOT>',
    ];

    
    if (false !== @\realpath($_SERVER['DOCUMENT_ROOT'])) {
        
        Utils::$path_aliases[\realpath($_SERVER['DOCUMENT_ROOT'])] = '<ROOT>';
    }
}

Utils::composerSkipFlags();

if ((!\defined('KINT_SKIP_FACADE') || !KINT_SKIP_FACADE) && !\class_exists('Kint')) {
    \class_alias(Kint::class, 'Kint');
}

if (!\defined('KINT_SKIP_HELPERS') || !KINT_SKIP_HELPERS) {
    require_once __DIR__.'/init_helpers.php';
}
