<?php

namespace Authorization;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Authorization {

    protected $session = null;
    protected $passwordFile = null;

    public function __construct($session) {
        $this->session = $session;
        $this->session->start();
    }

    public function setPasswordFile($file) {
        $this->passwordFile = $file;
    }

    public function isEnabled() {
        return (bool) $this->passwordFile;
    }

    public function isAuthenticated() {
        return $this->session->get('isAuthenticated', false);
    }

    public function authenticate() {
        if (!$this->isEnabled())
            return false;

        if (!file_exists($this->passwordFile))
            throw new \Exception('Password file don\'t exists.', 1);

        if ($this->session->get('logout', false)) {
            $this->session->remove('logout');
        } else {
            if ($this->session->get('isAuthenticated', false)) {
            return true;
            } else {
                $request = Request::createFromGlobals();

                $rUsername = $request->server->get('PHP_AUTH_USER', null);
                $rPassword = $request->server->get('PHP_AUTH_PW', null);

                if ($request->server->get('PHP_AUTH_USER', null) !== null) {
                    $users = array();
                    foreach(file($this->passwordFile) as $row) {
                        list($username, $password) = explode(':', $row);
                        $users[$username] = $password;
                    }

                    if (isset($users[$rUsername]) && $users[$rUsername] == $rPassword) {
                        $this->session->set('isAuthenticated', true);
                        return true;
                    }
                }
            }
        }

        $response = new Response;
        $response->headers->set('WWW-Authenticate', 'Basic realm="GitList"');
        $response->setStatusCode(401, 'Unauthorized.');
        $response->setContent('<h1>401 Unauthorized.</h1>');
        return $response;
    }

    public function logout() {
        $this->session->set('isAuthenticated', false);
        $this->session->set('logout', true);
    }
}