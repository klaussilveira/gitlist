<?php

declare(strict_types=1);

namespace GitList\SCM\Diff;

class Line
{
    public const TYPE_ADD = 'add';
    public const TYPE_DELETE = 'delete';
    public const TYPE_NO_CHANGE = 'no_change';

    public function __construct(protected string $contents, protected string $type = self::TYPE_NO_CHANGE, protected int $oldNumber = 0, protected int $newNumber = 0)
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function getOldNumber(): int
    {
        return $this->oldNumber;
    }

    public function getNewNumber(): int
    {
        return $this->newNumber;
    }
}
