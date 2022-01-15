<?php

declare(strict_types=1);

namespace GitList\SCM;

use Carbon\CarbonInterface;
use GitList\SCM\Commit\Person;
use GitList\SCM\Commit\Signature;
use GitList\SCM\Diff\File;

class Commit extends Item
{
    protected ?Tree $tree = null;
    protected ?Person $author = null;
    protected ?CarbonInterface $authoredAt = null;
    protected ?Person $commiter = null;
    protected ?CarbonInterface $commitedAt = null;
    protected ?Signature $signature = null;
    protected ?string $subject = null;
    protected ?string $body = null;

    /**
     * @var File[]
     */
    protected array $diffs = [];

    protected ?string $rawDiffs = null;

    public function getTree(): ?Tree
    {
        return $this->tree;
    }

    public function setTree(Tree $tree): void
    {
        $this->tree = $tree;
    }

    public function hasAuthor(): bool
    {
        return (bool) $this->author;
    }

    public function getAuthor(): ?Person
    {
        return $this->author;
    }

    public function setAuthor(Person $author): void
    {
        $this->author = $author;
    }

    public function getAuthoredAt(): ?CarbonInterface
    {
        return $this->authoredAt;
    }

    public function setAuthoredAt(CarbonInterface $authoredAt): void
    {
        $this->authoredAt = $authoredAt;
    }

    public function hasCommiter(): bool
    {
        return (bool) $this->commiter;
    }

    public function getCommiter(): ?Person
    {
        return $this->commiter;
    }

    public function setCommiter(Person $commiter): void
    {
        $this->commiter = $commiter;
    }

    public function getCommitedAt(): ?CarbonInterface
    {
        return $this->commitedAt;
    }

    public function setCommitedAt(CarbonInterface $commitedAt): void
    {
        $this->commitedAt = $commitedAt;
    }

    public function getSignature(): ?Signature
    {
        return $this->signature;
    }

    public function setSignature(Signature $signature): void
    {
        $this->signature = $signature;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getDiffs(): array
    {
        return $this->diffs;
    }

    public function setDiffs(array $diffs): void
    {
        $this->diffs = $diffs;
    }

    public function addDiff(File $diff): void
    {
        $this->diffs[] = $diff;
    }

    public function getRawDiffs(): ?string
    {
        return $this->rawDiffs;
    }

    public function setRawDiffs(string $rawDiffs): void
    {
        $this->rawDiffs = $rawDiffs;
    }

    public function getAdditions(): int
    {
        $additions = 0;

        foreach ($this->diffs as $diff) {
            $additions += $diff->getAdditions();
        }

        return $additions;
    }

    public function getDeletions(): int
    {
        $deletions = 0;

        foreach ($this->diffs as $diff) {
            $deletions += $diff->getDeletions();
        }

        return $deletions;
    }

    public function isCommit(): bool
    {
        return true;
    }

    public function isTree(): bool
    {
        return false;
    }

    public function isBlob(): bool
    {
        return false;
    }
}
