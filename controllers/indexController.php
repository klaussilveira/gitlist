<?php

$app->get('/', function() use($app) {
    $repositories = $app['git']->getRepositories($app['git.repos']);

    return $app['twig']->render('index.twig', array(
        'repositories'   => $repositories,
    ));
})->bind('homepage');
