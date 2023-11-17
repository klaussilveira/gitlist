<?php

declare(strict_types=1);

namespace GitList\SCM;

class Blob extends Item
{
    protected ?string $mode = null;
    protected ?string $name = null;
    protected ?int $size = null;
    protected ?string $contents = null;

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getFileName(): ?string
    {
        return basename($this->name ?? '');
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isReadme(): bool
    {
        $fileName = strtolower($this->getFileName());

        return 'readme.md' == $fileName || 'readme.txt' == $fileName;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function getContents(): ?string
    {
        return $this->contents;
    }

    public function setContents(string $contents): void
    {
        $this->contents = $contents;
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
        return true;
    }
}
