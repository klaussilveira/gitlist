<?php

declare(strict_types=1);

namespace GitList\SCM;

class Blame
{
    protected Repository $repository;

    /**
     * @var AnnotatedLine[]
     */
    protected array $annotatedLines = [];

    public function __construct(protected string $path, protected string $hash)
    {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getAnnotatedLines(): array
    {
        return $this->annotatedLines;
    }

    public function addAnnotatedLine(AnnotatedLine $annotatedLine): void
    {
        $this->annotatedLines[] = $annotatedLine;
    }
}
