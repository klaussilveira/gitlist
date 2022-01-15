<?php

declare(strict_types=1);

namespace GitList\SCM;

class Tree extends Item
{
    /**
     * @var Item[]
     */
    protected array $children = [];
    protected ?string $name = null;
    protected ?string $mode = null;

    public function addChild(Item $child): void
    {
        $child->addParent($this);
        $this->children[] = $child;
    }

    public function removeChild(Item $childToRemove): void
    {
        foreach ($this->children as $key => $child) {
            if ($child === $childToRemove) {
                unset($this->children[$key]);
            }
        }

        $this->children = array_values($this->children);
        $childToRemove->clearParents();
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    public function isChild(): bool
    {
        return !empty($this->parents);
    }

    public function isRoot(): bool
    {
        return empty($this->parents);
    }

    public function isLeaf(): bool
    {
        return empty($this->children);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getFileName(): ?string
    {
        return basename($this->name ?? '');
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function getReadme(): ?Blob
    {
        foreach ($this->children as $child) {
            if (!($child instanceof Blob)) {
                continue;
            }

            if ($child->isReadme()) {
                return $child;
            }
        }

        return null;
    }

    public function isCommit(): bool
    {
        return false;
    }

    public function isTree(): bool
    {
        return true;
    }

    public function isBlob(): bool
    {
        return false;
    }
}
