<?php

namespace GitList\Component\Git;

class BranchDiff
{
    protected $commits;
    protected $diffs;

    function setCommits($commits)
    {
        $this->commits = $commits;
    }

    function setDiffs($diffs)
    {
        $this->diffs = $diffs;
    }

    function getCommits()
    {
        return $this->commits;
    }

    function getDiffs()
    {
        return $this->diffs;
    }
}
