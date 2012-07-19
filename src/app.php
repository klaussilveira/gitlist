<?php

// Load configuration
$config = new GitList\Config(__DIR__.'/../config.ini');
$config->set('git', 'repositories', rtrim($config->get('git', 'repositories'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

// Startup and configure Silex application
$app = new GitList\Application($config, __DIR__.'/../');


return $app;
