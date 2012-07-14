<?php

namespace Application;

use Silex\Application;
use Silex\ServiceProviderInterface;

class UtilsServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Utils class on the Application ServiceProvider
     *
     * @param Application $app Silex Application
     */
    public function register(Application $app)
    {
        $app['utils'] = $app->share(function () use ($app) {
            return new Utils($app);
        });
    }

    public function boot(Application $app)
    {
    }
}
