<?php

declare(strict_types=1);

namespace GitList\SCM\Diff;

class File
{
    public const TYPE_NEW = 'new';
    public const TYPE_DELETED = 'deleted';
    public const TYPE_NO_CHANGE = 'no_change';
    protected string $type = self::TYPE_NO_CHANGE;
    protected ?string $index = null;
    protected string $from;
    protected string $to;
    protected int $additions = 0;
    protected int $deletions = 0;

    /**
     * @var Hunk[]
     */
    protected array $hunks = [];

    public function __construct(protected string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getIndex(): ?string
    {
        return $this->index;
    }

    public function setIndex(string $index): void
    {
        $this->index = $index;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function setFrom(string $from): void
    {
        $this->from = $from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function setTo(string $to): void
    {
        $this->to = $to;
    }

    public function getHunks(): array
    {
        return $this->hunks;
    }

    public function addHunk(Hunk $hunk): void
    {
        $this->hunks[] = $hunk;
    }

    public function getAdditions(): int
    {
        return $this->additions;
    }

    public function increaseAdditions(int $amount = 1): void
    {
        $this->additions += $amount;
    }

    public function getDeletions(): int
    {
        return $this->deletions;
    }

    public function increaseDeletions(int $amount = 1): void
    {
        $this->deletions += $amount;
    }
}
