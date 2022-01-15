<?php

declare(strict_types=1);

namespace GitList\SCM;

class AnnotatedLine
{
    public function __construct(protected Commit $commit, protected string $contents)
    {
    }

    public function getCommit(): Commit
    {
        return $this->commit;
    }

    public function getContents(): string
    {
        return $this->contents;
    }
}
