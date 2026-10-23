<?php

declare(strict_types=1);

namespace GitList\Repository;

use GitList\Repository;

class Commitish
{
    protected string $hash;
    protected ?string $path = null;

    public function __construct(Repository $repository, string $commitish)
    {
        $this->hash = (string) strtok($commitish, '/');
        $revs = [...$repository->getBranches(), ...$repository->getTags()];

        $matched = '';

        foreach ($revs as $rev) {
            $name = (string) $rev->getName();

            if (!str_starts_with($commitish, $name) || strlen($name) <= strlen($matched)) {
                continue;
            }

            $boundary = substr($commitish, strlen($name), 1);

            if ('' !== $boundary && !in_array($boundary, ['/', '@', '^', '~'], true)) {
                continue;
            }

            $matched = $name;
            $this->hash = $name;
            $revSuffix = substr($commitish, strlen($this->hash));

            if ($revSuffix && ('@' === $revSuffix[0] || '^' === $revSuffix[0] || '~' === $revSuffix[0])) {
                $this->hash .= strtok($revSuffix, '/');
            }
        }

        if ($this->hash != $commitish) {
            $this->path = substr($commitish, strlen($this->hash) + 1);
        }
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function hasPath(): bool
    {
        return (bool) $this->path;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }
}
