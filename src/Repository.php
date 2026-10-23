<?php

declare(strict_types=1);

namespace GitList;

use GitList\Exception\BlobNotFoundException;
use GitList\Exception\CommitNotFoundException;
use GitList\Repository\Attributes;
use GitList\Repository\Commitish;
use GitList\SCM\AnnotatedLine;
use GitList\SCM\AttributeRule;
use GitList\SCM\Blame;
use GitList\SCM\Blob;
use GitList\SCM\Branch;
use GitList\SCM\Commit;
use GitList\SCM\Commit\Criteria;
use GitList\SCM\Diff\File;
use GitList\SCM\Exception\CommandException;
use GitList\SCM\Hook;
use GitList\SCM\Repository as SourceRepository;
use GitList\SCM\System;
use GitList\SCM\Tag;
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

    /**
     * @return Branch[]
     */
    public function getBranches(): array
    {
        return $this->system->getBranches($this->repository);
    }

    /**
     * @return Tag[]
     */
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
            return $this->system->getPathTree($this->repository, $commitish->getPath() ?? '', $commitish->getHash());
        }

        return $this->system->getTree($this->repository, $commitish->getHash());
    }

    public function getCommit(?string $commitish = null): Commit
    {
        if (!$commitish) {
            return $this->system->getCommit($this->repository);
        }

        $commitish = new Commitish($this, $commitish);

        try {
            return $this->system->getCommit($this->repository, $commitish->getHash());
        } catch (CommandException $exception) {
            if ($exception->isNotFoundException()) {
                throw new CommitNotFoundException();
            }

            throw $exception;
        }
    }

    /**
     * @return array<string, Commit>
     */
    public function getCommits(?string $commitish, int $page, int $perPage): array
    {
        if (!$commitish) {
            return $this->system->getCommits($this->repository, null, $page, $perPage);
        }

        $commitish = new Commitish($this, $commitish);

        try {
            if ($commitish->hasPath()) {
                return $this->system->getCommitsFromPath($this->repository, $commitish->getPath() ?? '', $commitish->getHash(), $page, $perPage);
            }

            return $this->system->getCommits($this->repository, $commitish->getHash(), $page, $perPage);
        } catch (CommandException $exception) {
            if ($exception->isNotFoundException()) {
                throw new CommitNotFoundException();
            }

            throw $exception;
        }
    }

    /**
     * @param string[] $hashes
     *
     * @return array<string, Commit>
     */
    public function getSpecificCommits(array $hashes): array
    {
        return $this->system->getSpecificCommits($this->repository, $hashes);
    }

    public function getBlame(string $commitish): Blame
    {
        $commitish = new Commitish($this, $commitish);

        try {
            $blame = $this->system->getBlame($this->repository, $commitish->getHash(), $commitish->getPath() ?? '');
        } catch (CommandException $exception) {
            if ($exception->isNotFoundException()) {
                throw new BlobNotFoundException();
            }

            throw $exception;
        }

        $consolidatedBlame = new Blame($blame->getPath(), $blame->getHash());

        $annotatedLines = $blame->getAnnotatedLines();
        $lineAccumulator = '';
        foreach ($annotatedLines as $index => $currentLine) {
            $lineAccumulator .= $currentLine->getContents().PHP_EOL;
            $nextLine = $annotatedLines[$index + 1] ?? null;

            if (!$nextLine || $currentLine->getCommit() != $nextLine->getCommit()) {
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
            return $this->system->getBlob($this->repository, $commitish->getHash(), $commitish->getPath() ?? '');
        } catch (CommandException $exception) {
            if ($exception->isNotFoundException()) {
                throw new BlobNotFoundException();
            }

            throw $exception;
        }
    }

    /**
     * @return array<string, Commit>
     */
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

    /**
     * @return array<string, Commit>
     */
    public function getGraph(int $limit): array
    {
        return $this->system->getGraph($this->repository, $limit);
    }

    /**
     * @return array{int, int}
     */
    public function getAheadBehind(string $base, string $head): array
    {
        return $this->system->getAheadBehind($this->repository, $base, $head);
    }

    public function getMergeBase(string $base, string $head): ?Commit
    {
        $hash = $this->system->getMergeBase($this->repository, $base, $head);

        if (!$hash) {
            return null;
        }

        $commits = $this->system->getSpecificCommits($this->repository, [$hash]);

        return reset($commits) ?: null;
    }

    /**
     * @return array<string, Commit>
     */
    public function getCommitsBetween(string $base, string $head, int $limit): array
    {
        return $this->system->getCommitsBetween($this->repository, $base, $head, $limit);
    }

    /**
     * @return File[]
     */
    public function getDiffBetween(string $base, string $head): array
    {
        return $this->system->getDiffBetween($this->repository, $base, $head);
    }

    /**
     * @return list<array{type: string, name: string, hash: string}>
     */
    public function getRefs(): array
    {
        return $this->system->getRefs($this->repository);
    }

    /**
     * @return Hook[]
     */
    public function getHooks(): array
    {
        return $this->system->getHooks($this->repository);
    }

    /**
     * @return AttributeRule[]
     */
    public function getAttributes(Commitish $commitish): array
    {
        return $this->system->getAttributes($this->repository, $commitish->getHash());
    }

    /**
     * @return array<string, string|bool>
     */
    public function getFileAttributes(string $commitish): array
    {
        $commitish = new Commitish($this, $commitish);
        $path = $commitish->getPath() ?? '';
        $rules = $this->system->getAttributes($this->repository, $commitish->getHash(), $path);

        return (new Attributes($rules))->resolve($path);
    }

    /**
     * @return array{properties: array<string, string|int|bool>, remotes: array<string, string>, config: array<string, string>}
     */
    public function getInfo(): array
    {
        return $this->system->getInfo($this->repository);
    }

    /**
     * @return list<array{path: string, url: string, hash: ?string}>
     */
    public function getSubmodules(Commitish $commitish): array
    {
        return $this->system->getSubmodules($this->repository, $commitish->getHash());
    }

    /**
     * @return array<string, string>
     */
    public function getNotes(string $hash): array
    {
        return $this->system->getNotes($this->repository, $hash);
    }

    /**
     * @param string[] $branches
     */
    public function guessParentBranch(string $branch, array $branches): ?string
    {
        $descendants = $this->system->getBranchesContaining($this->repository, $branch);
        $others = array_values(array_filter($branches, fn (string $name): bool => '' !== $name && $name !== $branch && !in_array($name, $descendants, true)));

        if (!$others) {
            return null;
        }

        $forkPoint = $this->system->getForkPoint($this->repository, $branch, $others);

        if (!$forkPoint) {
            return null;
        }

        $candidates = array_values(array_intersect($this->system->getBranchesContaining($this->repository, $forkPoint), $others));

        if (in_array($this->getDefaultBranch(), $candidates, true)) {
            return $this->getDefaultBranch();
        }

        $closest = null;
        $distance = PHP_INT_MAX;

        foreach ($candidates as $candidate) {
            [$ahead] = $this->system->getAheadBehind($this->repository, $forkPoint, $candidate);

            if ($ahead < $distance) {
                $closest = $candidate;
                $distance = $ahead;
            }
        }

        return $closest;
    }
}
