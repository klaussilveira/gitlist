<?php

namespace Authorization;

class AuthorizationFilter {
    public static function before($app) {
        if ($app['authorization']->isEnabled()) {
            $app->before(function () use ($app) {
                return $app['authorization']->authenticate();
            });
        }
    }
}