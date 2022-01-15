<?php

declare(strict_types=1);

namespace GitList\SCM;

class Branch
{
    public function __construct(protected Repository $repository, protected string $name, protected Commit $target)
    {
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTarget(): Commit
    {
        return $this->target;
    }
}
