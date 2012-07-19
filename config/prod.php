<?php

// configure your app for the production environment
$app['debug'] = false;

// Git :
$app['git.client2']      = '/usr/bin/git'; // Your git executable path
$app['git.repositories'] = '/home/gregoire/dev/' ; // Path to your repositories; Do not forgot the trainlings /
$app['git.hidden2']      = array(
    '/var/www/dev/BetaTest',
); // You can hide repositories from GitList, just copy this for each repository you want to hide

// If you need to specify custom filetypes for certain extensions, do this here
//$app['filetypes.extension'] = 'type';
//$app['filetypes.dist'] = 'xml';
