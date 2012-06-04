<?php
use Symfony\Component\HttpFoundation\Request;

$app->get('/login', function() use ($app) {
    if ($app['auth']->isAuthenticated()) return $app->redirect($app['baseurl'].'/');
    return $app['twig']->render('login.twig', array('baseurl' => $app['baseurl'],));
});

$app->post('/login', function(Request $request) use ($app) {
    if ($app['auth']->doLogin($request->get('login'), $request->get('password')))
        return $app->redirect($app['baseurl'].'/');
    else 
    	return $app['twig']->render('login.twig', array('baseurl' => $app['baseurl'], 'error' => true));
});

$app->get('/logout', function() use ($app) {
    $app['auth']->doLogout();
    return $app->redirect($app['baseurl'].'/'); 
});