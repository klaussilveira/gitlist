<?php

/**
 * GitList 0.3
 * https://github.com/klaussilveira/gitlist
 */

if (!file_exists('config.ini')) {
    die("Please, create the config.ini file.");
}

$config = parse_ini_file('config.ini', true);

if (empty($config['git']['repositories']) || !is_dir($config['git']['repositories'])) {
    die("Please, edit the config.ini file and provide your repositories directory");
}

require 'vendor/autoload.php';

$app = new Application\Application(__DIR__, $config);

// Load controllers
$app->mount('', new Application\Controller\ArchiveController());
$app->mount('', new Application\Controller\IndexController());
$app->mount('', new Application\Controller\TreeController());
$app->mount('', new Application\Controller\BlobController());
$app->mount('', new Application\Controller\CommitController());
$app->mount('', new Application\Controller\StatsController());
$app->mount('', new Application\Controller\RssController());

$app->run();
