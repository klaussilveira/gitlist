<?php
namespace Auth;

class Loader {
    public static function run($app, $config) {
        if ((bool)$config['auth']['enable']) {
            $app->register(new \Silex\Provider\SessionServiceProvider());
            $app->register(new AuthServiceProvider());
            $app['auth']->setConfig($config);

            $app['dispatcher']->addListener(\Silex\SilexEvents::BEFORE, function (\Symfony\Component\EventDispatcher\Event $event) use ($app) {
                if (!$app['auth']->isAuthenticated() && $app['request']->getPathInfo() != '/login') {
                    $event->setResponse($app->redirect($app['baseurl'].'/login'));
                }
            }, 0);

            include 'controllers/authController.php';
        }
    }
}