<?php

namespace GitList\Provider;

use GitList\Component\Git\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class GitServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Git\Client on the Application ServiceProvider
     *
     * @param  Application $app Silex Application
     * @return Git\Client  Instance of the Git\Client
     */
    public function register(Application $app)
    {
        $app['git'] = function($app) {
            $options = array(
                'path'   => $app['git.client'],
                'hidden' => $app['git.hidden'],
            );

            return new Client($options);
        };
    }

    public function boot(Application $app)
    {
    }
}
