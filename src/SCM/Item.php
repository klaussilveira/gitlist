<?php

declare(strict_types=1);

namespace GitList\SCM;

class Item
{
    /**
     * @var self[]
     */
    protected array $parents = [];

    public function __construct(protected Repository $repository, protected string $hash, protected ?string $shortHash = null)
    {
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getShortHash(): ?string
    {
        return $this->shortHash;
    }

    public function setShortHash(string $shortHash): void
    {
        $this->shortHash = $shortHash;
    }

    public function getParents(): array
    {
        return $this->parents;
    }

    public function clearParents(): void
    {
        $this->parents = [];
    }

    public function addParent(self $parent): void
    {
        $this->parents[] = $parent;
    }

    public function getFirstParent(): ?self
    {
        return $this->parents[0] ?? null;
    }

    public function isCommit(): bool
    {
        return false;
    }

    public function isTree(): bool
    {
        return false;
    }

    public function isBlob(): bool
    {
        return false;
    }
}
