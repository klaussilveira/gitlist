<?php

namespace GitList\Component\Git\Model;

use GitList\Component\Git\Client;
use GitList\Component\Git\Repository;
use GitList\Component\Git\ScopeAware;

class Blob extends ScopeAware
{
    protected $mode;
    protected $hash;
    protected $name;
    protected $age;
    protected $size;

    public function __construct($hash, Client $client, Repository $repository)
    {
        $this->setClient($client);
        $this->setRepository($repository);
        $this->setHash($hash);
    }

    public function output()
    {
        $data = $this->getClient()->run($this->getRepository(), 'show ' . $this->getHash());

        return $data;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function setAge($age)
    {
        $this->age = $age;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function setSize($size)
    {
        $this->size = $size;
    }
}
