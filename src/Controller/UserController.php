<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;


class UserController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $app->get('/user/login', function(Request $request) use ($app) {

            return $app['twig']->render('login.twig', array(
                'error'         => $app['security.last_error']($request),
                'last_username' => $app['session']->get('_security.last_username'),
            ));
        });

        return $route;
    }
}