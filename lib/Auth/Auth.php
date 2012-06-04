<?php
namespace Auth;

class Auth {

    protected $app = null;
    protected $config = null;

    public function __construct($app) {
        $this->app = $app;
        $this->app['session']->start();
    }

    public function setConfig($config) {
        $this->config = $config;
    }

    public function isAuthenticated() {
        return $this->app['session']->get('isAuthenticated', false);
    }

    public function doLogin($login, $password) {
        if (!empty($login) && !empty($password) && $login == $password){
            $this->app['session']->set('isAuthenticated', true);
            return true;
        }
        return false;
    }

    public function doLogout() {
        $this->app['session']->set('isAuthenticated', false);
    }

    public function isEnabled() {
        return (bool) $this->config['auth']['enable'];
    }
}