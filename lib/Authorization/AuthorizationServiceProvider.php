<?php

namespace Authorization;

use Silex\Application;
use Silex\ServiceProviderInterface;

class AuthorizationServiceProvider implements ServiceProviderInterface {
    
    public function register(Application $app) {
        $app['auth'] = $app->share(function () use ($app) {
            $auth = new Authorization($app['session']);
            $auth->setPasswordFile($app['auth.file']);
            return $auth;
        });
    }
}