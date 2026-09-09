<?php

declare(strict_types=1);

$path = urldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$root = __DIR__.'/../public';
$file = realpath($root.$path);

if ('/' !== $path && false !== $file && is_file($file) && str_starts_with($file, (string) realpath($root))) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root.'/index.php';

require $root.'/index.php';
