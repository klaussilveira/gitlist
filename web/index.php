<?php

ini_set('display_errors', 0);

require_once __DIR__.'/../vendor/autoload.php';

$app = new GitList\Application();
require __DIR__.'/../src/controllers.php';

$app->run();
