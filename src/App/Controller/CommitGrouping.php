<?php

declare(strict_types=1);

namespace GitList\App\Controller;

use GitList\SCM\Commit as SourceCommit;

trait CommitGrouping
{
    /**
     * @param array<string, SourceCommit> $commits
     *
     * @return array<string, list<SourceCommit>>
     */
    protected function groupCommitsByDate(array $commits): array
    {
        $commitGroups = [];

        foreach ($commits as $commit) {
            $commitGroups[$commit->getCommitedAt()?->format('Y-m-d') ?? ''][] = $commit;
        }

        return $commitGroups;
    }
}
