<?php

namespace GitList\Provider;

use GitList\Util\View;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ViewUtilServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['util.view'] = function () {
            return new View();
        };
    }
}
