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
            $options['encoding.enable'] = $app['encoding.enable'];
            $options['encoding.detect_order'] = $app['encoding.detect_order'];
            $options['encoding.search_all'] = $app['encoding.search_all'];
            $options['encoding.fallback'] = $app['encoding.fallback'];
            $options['encoding.convert_to'] = $app['encoding.convert_to'];

            return new Client($options);
        };
    }

    public function boot(Application $app)
    {
    }
}
