<?php

namespace Authorization;

class AuthorizationFilter {
    public static function before($app) {
        if ($app['auth']->isEnabled()) {
            $app->before(function () use ($app) {
                return $app['auth']->authenticate();
            });
        }
    }
}