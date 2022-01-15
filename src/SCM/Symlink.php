<?php

declare(strict_types=1);

namespace GitList\SCM;

class Symlink extends Blob
{
    protected string $target;

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): void
    {
        $this->target = $target;
    }
}
