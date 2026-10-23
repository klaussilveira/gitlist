<?php

declare(strict_types=1);

namespace GitList\SCM;

use Carbon\CarbonImmutable;

class Hook
{
    public function __construct(protected string $name, protected string $contents, protected bool $executable, protected bool $sample, protected CarbonImmutable $modifiedAt)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function isExecutable(): bool
    {
        return $this->executable;
    }

    public function isSample(): bool
    {
        return $this->sample;
    }

    public function isActive(): bool
    {
        return $this->executable && !$this->sample;
    }

    public function getModifiedAt(): CarbonImmutable
    {
        return $this->modifiedAt;
    }

    public function getSize(): int
    {
        return strlen($this->contents);
    }

    public function getChecksum(): string
    {
        return substr(hash('sha256', $this->contents), 0, 12);
    }
}
