<?php

namespace GitList\Provider;

use GitList\Util\Repository;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class RepositoryUtilServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['util.repository'] = function () use ($container) {
            return new Repository($container);
        };
    }
}
