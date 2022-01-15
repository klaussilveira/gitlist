<?php

declare(strict_types=1);

namespace GitList\SCM\Diff;

class Hunk
{
    /**
     * @var Line[]
     */
    protected array $lines = [];

    public function __construct(protected string $contents, protected int $oldStart, protected int $oldCount, protected int $newStart, protected int $newCount)
    {
    }

    public function getOldStart(): int
    {
        return $this->oldStart;
    }

    public function getOldCount(): int
    {
        return $this->oldCount;
    }

    public function getNewStart(): int
    {
        return $this->newStart;
    }

    public function getNewCount(): int
    {
        return $this->newCount;
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function getLines(): array
    {
        return $this->lines;
    }

    public function addLine(Line $line): void
    {
        $this->lines[] = $line;
    }
}
