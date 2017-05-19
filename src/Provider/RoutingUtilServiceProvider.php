<?php

namespace GitList\Provider;

use GitList\Util\Routing;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class RoutingUtilServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Util\Repository class on the Application ServiceProvider
     *
     * @param Container $app Silex Application
     */
    public function register(Container $app)
    {
        $app['util.routing'] = $app->factory(function () use ($app) {
            return new Routing($app);
        });
    }

    public function boot(Container $app)
    {
    }
}
