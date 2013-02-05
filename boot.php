<?php

if (!isset($config)) {
    die("No configuration object provided.");
}

$repositories = $config->get('git', 'repositories');

if (!is_array($repositories)) {
    # Convert the single item to an array - this is the internal handling
    $repositories  = array($repositories);
}

$tmp_arr = array();
foreach ($repositories as $repo) {
    $tmp = rtrim($repo, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $tmp_arr []= $tmp;
}
$repositories = $tmp_arr;



// Startup and configure Silex application
$app = new GitList\Application($config, __DIR__);


// Mount the controllers
$app->mount('', new GitList\Controller\MainController());
$app->mount('', new GitList\Controller\BlobController());
$app->mount('', new GitList\Controller\CommitController());
$app->mount('', new GitList\Controller\TreeController());


return $app;

