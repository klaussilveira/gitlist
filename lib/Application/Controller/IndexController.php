<?php

namespace Application\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;

class IndexController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function() use ($app) {
            $repositories = $app['git']->getRepositories($app['git.repos']);

            return $app['twig']->render('index.twig', array(
                'repositories'   => $repositories,
            ));
        })->bind('homepage');

        return $controllers;
    }
}
