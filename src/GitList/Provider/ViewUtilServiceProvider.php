<?php

namespace GitList\Provider;

use GitList\Util\View;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ViewUtilServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Util\Interface class on the Application ServiceProvider
     *
     * @param Application $app Silex Application
     */
    public function register(Application $app)
    {
        $app['util.view'] = $app->share(function () {
            return new View;
        });
    }

    public function boot(Application $app)
    {
    }
}
