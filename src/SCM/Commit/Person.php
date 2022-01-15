<?php

declare(strict_types=1);

namespace GitList\SCM\Commit;

class Person
{
    public function __construct(protected string $name, protected string $email)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
