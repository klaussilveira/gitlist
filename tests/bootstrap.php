<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

$repositories = __DIR__.'/fixtures';

putenv('DEFAULT_REPOSITORY_DIR='.$repositories);
$_ENV['DEFAULT_REPOSITORY_DIR'] = $repositories;
$_SERVER['DEFAULT_REPOSITORY_DIR'] = $repositories;
