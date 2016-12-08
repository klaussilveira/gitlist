<?php

namespace GitList\Provider;

use GitList\Git\Client;
use GitList\Git\GitoliteClient;
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
        $app['git'] = function () use ($app) {
            $options['path'] = $app['git.client'];
            $options['hidden'] = $app['git.hidden'];
            $options['projects'] = $app['git.projects'];
            $options['ini.file'] = $app['ini.file'];
            $options['default_branch'] = $app['git.default_branch'];

            if($app['gitolite.active']) {
                $options['gitolite.wrapper_path'] = $app['gitolite.wrapper_path'];
                $options['username'] = $app['username'];

                return new GitoliteClient($options);
            }

            return new Client($options);
        };
    }

    public function boot(Application $app)
    {
    }
}
