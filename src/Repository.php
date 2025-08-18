<?php

declare(strict_types=1);

namespace GitList;

use GitList\Exception\BlobNotFoundException;
use GitList\Repository\Commitish;
use GitList\SCM\AnnotatedLine;
use GitList\SCM\Blame;
use GitList\SCM\Blob;
use GitList\SCM\Commit;
use GitList\SCM\Commit\Criteria;
use GitList\SCM\Exception\CommandException;
use GitList\SCM\Repository as SourceRepository;
use GitList\SCM\System;
use GitList\SCM\Tree;

class Repository
{
    public function __construct(protected System $system, protected SourceRepository $repository, protected string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->system->getDescription($this->repository);
    }

    public function getDefaultBranch(): string
    {
        return $this->system->getDefaultBranch($this->repository);
    }

    public function getBranches(): array
    {
        return $this->system->getBranches($this->repository);
    }

    public function getTags(): array
    {
        return $this->system->getTags($this->repository);
    }

    public function getTree(?string $commitish = null): Tree
    {
        if (!$commitish) {
            return $this->system->getTree($this->repository);
        }

        $commitish = new Commitish($this, $commitish);

        if ($commitish->hasPath()) {
            return $this->system->getPathTree($this->repository, $commitish->getPath(), $commitish->getHash());
        }

        return $this->system->getTree($this->repository, $commitish->getHash());
    }

    public function getCommit(?string $commitish = null): Commit
    {
        if (!$commitish) {
            return $this->system->getCommit($this->repository);
        }

        $commitish = new Commitish($this, $commitish);

        return $this->system->getCommit($this->repository, $commitish->getHash());
    }

    public function getCommits(?string $commitish, int $page, int $perPage): array
    {
        if (!$commitish) {
            return $this->system->getCommits($this->repository, null, $page, $perPage);
        }

        $commitish = new Commitish($this, $commitish);

        if ($commitish->hasPath()) {
            return $this->system->getCommitsFromPath($this->repository, $commitish->getPath(), $commitish->getHash(), $page, $perPage);
        }

        return $this->system->getCommits($this->repository, $commitish->getHash(), $page, $perPage);
    }

    public function getSpecificCommits(array $hashes): array
    {
        return $this->system->getSpecificCommits($this->repository, $hashes);
    }

    public function getBlame(string $commitish): Blame
    {
        $commitish = new Commitish($this, $commitish);
        $blame = $this->system->getBlame($this->repository, $commitish->getHash(), $commitish->getPath());
        $consolidatedBlame = new Blame($blame->getPath(), $blame->getHash());

        $annotatedLines = $blame->getAnnotatedLines();
        $lineAccumulator = '';
        foreach ($annotatedLines as $index => $currentLine) {
            $lineAccumulator .= $currentLine->getContents().PHP_EOL;
            $nextLine = $annotatedLines[$index + 1] ?? null;

            if ($nextLine && $currentLine->getCommit() != $nextLine->getCommit()) {
                $consolidatedBlame->addAnnotatedLine(new AnnotatedLine($currentLine->getCommit(), $lineAccumulator));
                $lineAccumulator = '';
            }
        }

        return $consolidatedBlame;
    }

    public function getBlob(string $commitish): Blob
    {
        $commitish = new Commitish($this, $commitish);

        try {
            return $this->system->getBlob($this->repository, $commitish->getHash(), $commitish->getPath());
        } catch (CommandException $exception) {
            if ($exception->isNotFoundException()) {
                throw new BlobNotFoundException();
            }

            throw $exception;
        }
    }

    public function searchCommits(Criteria $criteria, ?string $commitish = null): array
    {
        if (!$commitish) {
            return $this->system->searchCommits($this->repository, $criteria);
        }

        $commitish = new Commitish($this, $commitish);

        return $this->system->searchCommits($this->repository, $criteria, $commitish->getHash());
    }

    public function archive(string $format, string $commitish): string
    {
        $commitish = new Commitish($this, $commitish);

        return $this->system->archive($this->repository, $format, $commitish->getHash(), $commitish->getPath() ?? '.');
    }
}
