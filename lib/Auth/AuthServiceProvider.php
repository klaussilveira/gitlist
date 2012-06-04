<?php
namespace Auth;

use Silex\Application;
use Silex\ServiceProviderInterface;

class AuthServiceProvider implements ServiceProviderInterface {
    
    public function register(Application $app) {
        $app['auth'] = $app->share(function () use ($app) {
            return new Auth($app);
        });
    }
}