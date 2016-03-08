<?php

// Startup and configure Silex application
$app = new GitList\Application($config, __DIR__);

// Mount the controllers
$app->mount('', new GitList\Controller\MainController());
$app->mount('', new GitList\Controller\BlobController());
$app->mount('', new GitList\Controller\CommitController());
$app->mount('', new GitList\Controller\TreeController());
$app->mount('', new GitList\Controller\NetworkController());
$app->mount('', new GitList\Controller\TreeGraphController());

return $app;
