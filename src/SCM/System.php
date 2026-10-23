<?php

declare(strict_types=1);

namespace GitList\SCM;

use GitList\SCM\Commit\Criteria;

interface System
{
    public function isValidRepository(Repository $repository): bool;

    public function getDescription(Repository $repository): string;

    public function getDefaultBranch(Repository $repository): string;

    /**
     * @return Branch[]
     */
    public function getBranches(Repository $repository): array;

    /**
     * @return Tag[]
     */
    public function getTags(Repository $repository): array;

    public function getTree(Repository $repository, ?string $hash = null): Tree;

    public function getRecursiveTree(Repository $repository, ?string $hash = null): Tree;

    public function getPathTree(Repository $repository, string $path, ?string $hash = null): Tree;

    public function getCommit(Repository $repository, ?string $hash = null): Commit;

    /**
     * @return array<string, Commit>
     */
    public function getCommits(Repository $repository, ?string $hash = null, int $page = 1, int $perPage = 10): array;

    /**
     * @return array<string, Commit>
     */
    public function getCommitsFromPath(Repository $repository, string $path, ?string $hash = null, int $page = 1, int $perPage = 10): array;

    /**
     * @param string[] $hashes
     *
     * @return array<string, Commit>
     */
    public function getSpecificCommits(Repository $repository, array $hashes): array;

    public function getBlame(Repository $repository, string $hash, string $path): Blame;

    public function getBlob(Repository $repository, string $hash, string $path): Blob;

    /**
     * @return array<string, Commit>
     */
    public function searchCommits(Repository $repository, Criteria $criteria, ?string $hash = null): array;

    public function archive(Repository $repository, string $format, string $hash, string $path): string;

    /**
     * @return array<string, Commit>
     */
    public function getGraph(Repository $repository, int $limit): array;

    /**
     * @return array{int, int}
     */
    public function getAheadBehind(Repository $repository, string $base, string $head): array;

    public function getMergeBase(Repository $repository, string $base, string $head): ?string;

    /**
     * @return array<string, Commit>
     */
    public function getCommitsBetween(Repository $repository, string $base, string $head, int $limit): array;

    /**
     * @return Diff\File[]
     */
    public function getDiffBetween(Repository $repository, string $base, string $head): array;

    /**
     * @param string[] $excluded
     */
    public function getForkPoint(Repository $repository, string $head, array $excluded): ?string;

    /**
     * @return string[]
     */
    public function getBranchesContaining(Repository $repository, string $hash): array;

    /**
     * @return list<array{type: string, name: string, hash: string}>
     */
    public function getRefs(Repository $repository): array;
}
