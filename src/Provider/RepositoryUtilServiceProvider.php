<?php

namespace GitList\Provider;

use GitList\Util\Repository;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RepositoryUtilServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['util.repository'] = function () use ($app) {
            return new Repository($app);
        };
    }

    public function boot(Application $app)
    {
    }
}
