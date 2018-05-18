<?php

namespace GitList\Provider;

use GitList\Util\View;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ViewUtilServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['util.view'] = function () {
            return new View();
        };
    }

    public function boot(Application $app)
    {
    }
}
