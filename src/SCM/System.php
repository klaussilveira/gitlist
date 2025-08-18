<?php

declare(strict_types=1);

namespace GitList\SCM;

use GitList\SCM\Commit\Criteria;

interface System
{
    public function isValidRepository(Repository $repository): bool;

    public function getDescription(Repository $repository): string;

    public function getDefaultBranch(Repository $repository): string;

    public function getBranches(Repository $repository): array;

    public function getTags(Repository $repository): array;

    public function getTree(Repository $repository, ?string $hash = null): Tree;

    public function getRecursiveTree(Repository $repository, ?string $hash = null): Tree;

    public function getPathTree(Repository $repository, string $path, ?string $hash = null): Tree;

    public function getCommit(Repository $repository, ?string $hash = null): Commit;

    public function getCommits(Repository $repository, ?string $hash = null, int $page = 1, int $perPage = 10): array;

    public function getCommitsFromPath(Repository $repository, string $path, ?string $hash = null, int $page = 1, int $perPage = 10): array;

    public function getSpecificCommits(Repository $repository, array $hashes): array;

    public function getBlame(Repository $repository, string $hash, string $path): Blame;

    public function getBlob(Repository $repository, string $hash, string $path): Blob;

    public function searchCommits(Repository $repository, Criteria $criteria, ?string $hash = null): array;

    public function archive(Repository $repository, string $format, string $hash, string $path): string;
}
