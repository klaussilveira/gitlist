<?php

if (version_compare(PHP_VERSION, '5', 'lt')) {
    exit;
}

if (stristr(PHP_OS, 'WIN')) {
    define('WINDOWS_BUILD', 1);
}

// Load configuration
$config = new GitList\Config('config.ini');
$config->set('git', 'repositories', rtrim($config->get('git', 'repositories'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

// Startup and configure Silex application
$app = new GitList\Application($config, __DIR__);

// Mount the controllers
$app->mount('', new GitList\Controller\MainController());
$app->mount('', new GitList\Controller\BlobController());
$app->mount('', new GitList\Controller\CommitController());
$app->mount('', new GitList\Controller\TreeController());

return $app;