<?php

namespace GitList\Provider;

use GitList\Util\View;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ViewUtilServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Util\Interface class on the Application ServiceProvider
     *
     * @param Application $app Silex Application
     */
    public function register(Container $app)
    {
        $app['util.view'] = $app->factory(function () {
            return new View;
        });
    }

    public function boot(Container $app)
    {
    }
}
