<?php

ini_set('display_errors', 1);
error_reporting(-1);

require_once __DIR__.'/../vendor/autoload.php';

$app = new GitList\Application('dev');
require __DIR__.'/../src/controllers.php';

$app->run();
