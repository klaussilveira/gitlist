<?php

namespace GitList\Provider;

use GitList\Util\Routing;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RoutingUtilServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Util\Repository class on the Application ServiceProvider
     *
     * @param Application $app Silex Application
     */
    public function register(Application $app)
    {
        $app['util.routing'] = $app->share(function () use ($app) {
            return new Routing($app);
        });
    }

    public function boot(Application $app)
    {
    }
}
