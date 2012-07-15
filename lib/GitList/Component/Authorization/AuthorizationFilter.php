<?php

namespace GitList\Component\Authorization;

use Silex\Application;

class AuthorizationFilter {
    public static function before(\Silex\Application $app) {
        if ($app['authorization']->isEnabled()) {
            $app->before(function () use ($app) {
                return $app['authorization']->authenticate();
            });
        }
    }
}