<?php

namespace GitList\Provider;

use GitList\Component\Authorization\Authorization;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AuthorizationServiceProvider implements ServiceProviderInterface {
    
    public function register(Application $app) {
        $app['authorization'] = $app->share(function () use ($app) {
            $auth = new Authorization($app['session']);
            $auth->setPasswordFile($app['authorization.file']);
            return $auth;
        });
    }

    public function boot(Application $app) {
        if ($app['authorization']->isEnabled()) {
            $app->before(function () use ($app) {
                return $app['authorization']->authenticate();
            });
        }
    }
}
