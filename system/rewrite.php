<?php

declare(strict_types=1);






$uri = urldecode(
    parse_url('https://codeigniter.com' . $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '',
);


$_SERVER['SCRIPT_NAME'] = '/index.php';


$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . ltrim($uri, '/');



if ($uri !== '/' && (is_file($path) || is_dir($path))) {
    return false;
}

unset($uri, $path);



require_once $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'index.php';

