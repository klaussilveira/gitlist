<?php

namespace GitList\Provider;

use GitList\Git\Client;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class GitServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['git'] = function () use ($container) {
            $options['path'] = $container['git.client'];
            $options['hidden'] = $container['git.hidden'];
            $options['projects'] = $container['git.projects'];
            $options['ini.file'] = $container['ini.file'];
            $options['default_branch'] = $container['git.default_branch'];

            return new Client($options);
        };
    }
}
