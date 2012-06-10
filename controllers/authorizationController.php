<?php

$app->get('/logout', function() use ($app) {
    $app['authorization']->logout();
    return $app->redirect($app['baseurl'].'/'); 
});