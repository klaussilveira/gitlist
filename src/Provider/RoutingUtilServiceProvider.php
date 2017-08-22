<?php

namespace GitList\Provider;

use GitList\Util\Routing;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class RoutingUtilServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['util.routing'] = function () use ($container) {
            return new Routing($container);
        };
    }
}
