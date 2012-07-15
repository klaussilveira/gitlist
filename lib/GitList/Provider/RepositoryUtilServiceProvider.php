<?php

namespace GitList\Provider;

use GitList\Util\Repository;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RepositoryUtilServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Util\Repository class on the Application ServiceProvider
     *
     * @param Application $app Silex Application
     */
    public function register(Application $app)
    {
        $app['util.repository'] = $app->share(function () use ($app) {
            return new Repository($app);
        });
    }

    public function boot(Application $app)
    {
    }
}
