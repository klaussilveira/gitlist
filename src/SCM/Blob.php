<?php

declare(strict_types=1);

namespace GitList\SCM;

class Blob extends Item
{
    public const DOCUMENT_TYPES = [
        'readme' => 'readme',
        'contributing' => 'contributing',
        'license' => 'license',
        'copying' => 'license',
        'security' => 'security',
    ];

    public const DOCUMENT_EXTENSIONS = ['', 'md', 'txt'];

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

    public function getFileName(): string
    {
        return basename($this->name ?? '');
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isReadme(): bool
    {
        return 'readme' == $this->getDocumentType();
    }

    public function getDocumentType(): ?string
    {
        $fileName = strtolower($this->getFileName());

        if (!in_array(pathinfo($fileName, PATHINFO_EXTENSION), self::DOCUMENT_EXTENSIONS, true)) {
            return null;
        }

        return self::DOCUMENT_TYPES[pathinfo($fileName, PATHINFO_FILENAME)] ?? null;
    }

    public function isMarkdown(): bool
    {
        $fileName = strtolower($this->getFileName());

        return str_ends_with($fileName, '.md') || 'readme.txt' == $fileName;
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
