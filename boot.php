<?php

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    define('WINDOWS_BUILD', 1);
} else {
    define('WINDOWS_BUILD', 0);
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