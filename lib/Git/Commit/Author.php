<?php

namespace Git\Commit;

class Author
{
    protected $name;
    protected $email;

    public function __construct($name, $email)
    {
        $this->setName($name);
        $this->setEmail($email);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }
}