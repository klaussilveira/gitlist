<?php

namespace GitList\Provider;

use GitList\Util\Routing;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RoutingUtilServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['util.routing'] = function () use ($app) {
            return new Routing($app);
        };
    }

    public function boot(Application $app)
    {
    }
}
