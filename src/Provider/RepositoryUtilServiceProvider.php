<?php

namespace GitList\Provider;

use GitList\Util\Repository;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class RepositoryUtilServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Util\Repository class on the Application ServiceProvider
     *
     * @param Container $app Silex Application
     */
    public function register(Container $app)
    {
        $app['util.repository'] = $app->factory(function () use ($app) {
            return new Repository($app);
        });
    }

    public function boot(Container $app)
    {
    }
}
