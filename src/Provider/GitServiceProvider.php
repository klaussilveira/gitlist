<?php

namespace GitList\Provider;

use GitList\Git\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class GitServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['git'] = function () use ($app) {
            $options['path'] = $app['git.client'];
            $options['hidden'] = $app['git.hidden'];
            $options['projects'] = $app['git.projects'];
            $options['ini.file'] = $app['ini.file'];
            $options['default_branch'] = $app['git.default_branch'];
            $options['strip_dot_git'] = $app['git.strip_dot_git'];

            return new Client($options);
        };
    }

    public function boot(Application $app)
    {
    }
}
