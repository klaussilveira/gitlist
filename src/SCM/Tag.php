<?php

declare(strict_types=1);

namespace GitList\SCM;

use Carbon\CarbonInterface;
use GitList\SCM\Commit\Person;

class Tag
{
    protected ?string $subject = null;
    protected ?Commit $target = null;

    public function __construct(protected Repository $repository, protected string $name, protected Person $author, protected CarbonInterface $authoredAt)
    {
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getTarget(): ?Commit
    {
        return $this->target;
    }

    public function setTarget(Commit $target): void
    {
        $this->target = $target;
    }

    public function getAuthor(): Person
    {
        return $this->author;
    }

    public function getAuthoredAt(): CarbonInterface
    {
        return $this->authoredAt;
    }
}
