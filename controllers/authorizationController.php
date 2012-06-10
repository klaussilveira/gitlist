<?php

$app->get('/logout', function() use ($app) {
    $app['auth']->logout();
    return $app->redirect($app['baseurl'].'/'); 
});