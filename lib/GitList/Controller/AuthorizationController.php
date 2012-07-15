<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

class AuthorizationController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('/logout', function() use ($app) {
            $app['authorization']->logout();
            return $app->redirect($app['url_generator']->generate('homepage')); 
        });
        
        return $route;
    }
}