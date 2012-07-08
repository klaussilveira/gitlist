<?php

namespace Git;

use Silex\Application;
use Silex\ServiceProviderInterface;

class GitServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Git\Client on the Application ServiceProvider
     * 
     * @param Application $app Silex Application
     * @return Git\Client Instance of the Git\Client
     */
    public function register(Application $app)
    {
        $app['git'] = function () use ($app) {
            return new Client($app);
        };
    }

    public function boot(Application $app)
    {
    }
}