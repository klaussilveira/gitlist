<?php

namespace GitList\Component\Authorization;

use Symfony\Component\HttpFoundation\Response;

class Authorization {

    protected $session = null;
    protected $passwordFile = null;

    public function __construct($session, $request = null) {
        $this->session = $session;
        $this->request = $request;
        $this->session->start();
    }

    public function setRequest($request) {
        $this->request = $request;
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
            throw new \RuntimeException('Password file don\'t exists.', 1);

        if ($this->session->get('logout', false)) {
            $this->session->remove('logout');
        } else {
            if ($this->session->get('isAuthenticated', false)) {
                return true;
            } else {
                $rUsername = $this->request->server->get('PHP_AUTH_USER', null);
                $rPassword = $this->request->server->get('PHP_AUTH_PW', null);

                if ($this->request->server->get('PHP_AUTH_USER', null) !== null) {
                    foreach(file($this->passwordFile, FILE_IGNORE_NEW_LINES) as $row) {
                        list($username, $password) = explode(':', $row);

                        if ($username === $rUsername && $password === $rPassword) {
                            $this->session->set('isAuthenticated', true);
                            return true;
                        }
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
