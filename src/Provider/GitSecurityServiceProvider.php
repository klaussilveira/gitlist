<?php

namespace GitList\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class GitSecurityServiceProvider extends SecurityServiceProvider
{

    /**
     * Register the GitSecurityServiceProvider on the Application ServiceProvider
     *
     * @param  Application $app Silex Application
     */
    public function register(Application $app)
    {
        // Use another encoder for password.
        $app['security.encoder.digest'] = $app->share(function () {
            // use the sha1 algorithm
            // don't base64 encode the password
            // use only 1 iteration
            return new MessageDigestPasswordEncoder('sha1', false, 1);
        });
    }

    public function boot(Application $app)
    {
    }
}