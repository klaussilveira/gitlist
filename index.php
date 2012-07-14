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
include 'controllers/archiveController.php';
include 'controllers/indexController.php';
include 'controllers/treeController.php';
include 'controllers/blobController.php';
include 'controllers/commitController.php';
include 'controllers/statsController.php';
include 'controllers/rssController.php';

$app->run();
