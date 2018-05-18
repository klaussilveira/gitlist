<?php

namespace GitList\Util;

use GitList\Exception\EmptyRepositoryException;
use Silex\Application;

class Routing
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /* @brief Return $commitish, $path parsed from $commitishPath, based on
     * what's in $repo. Raise a 404 if $branchpath does not represent a
     * valid branch and path.
     *
     * A helper for parsing routes that use commit-ish names and paths
     * separated by /, since route regexes are not enough to get that right.
     *
     * @param string $commitishPath
     * @param string $repo
     * @return array
     */
    public function parseCommitishPathParam($commitishPath, $repo)
    {
        $app = $this->app;
        $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

        $commitish = null;
        $path = null;

        $slashPosition = strpos($commitishPath, '/');
        if (strlen($commitishPath) >= 40 &&
            ($slashPosition === false ||
             $slashPosition === 40)) {
            // We may have a commit hash as our commitish.
            $hash = substr($commitishPath, 0, 40);
            if (preg_match('/[^a-zA-Z0-9]/i', $hash) === 0) {
                if ($repository->hasCommit($hash)) {
                    $commitish = $hash;
                }
            }
        }

        if ($commitish === null) {
            $branches = $repository->getBranches();

            $tags = $repository->getTags();
            if ($tags !== null && count($tags) > 0) {
                $branches = array_merge($branches, $tags);
            }

            $matchedBranch = null;
            $matchedBranchLength = 0;
            foreach ($branches as $branch) {
                if (strpos($commitishPath, $branch) === 0 &&
                    strlen($branch) > $matchedBranchLength) {
                    $matchedBranch = $branch;
                    $matchedBranchLength = strlen($matchedBranch);
                }
            }

            if ($matchedBranch !== null) {
                $commitish = $matchedBranch;
            } else {
                // We may have partial commit hash as our commitish.
                $hash = $slashPosition === false ? $commitishPath : substr($commitishPath, 0, $slashPosition);
                if ($repository->hasCommit($hash)) {
                    $commit = $repository->getCommit($hash);
                    $commitish = $commit->getHash();
                } else {
                    throw new EmptyRepositoryException('This repository is currently empty. There are no commits.');
                }
            }
        }

        $commitishLength = strlen($commitish);
        $path = substr($commitishPath, $commitishLength);
        if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }

        return array($commitish, $path);
    }

    public function getBranchRegex()
    {
        static $branchRegex = null;

        if ($branchRegex === null) {
            $branchRegex = '(?!/|.*([/.]\.|//|@\{|\\\\))[^\040\177 ~^:?*\[]+(?<!\.lock|[/.])';
        }

        return $branchRegex;
    }

    public function getCommitishPathRegex()
    {
        static $commitishPathRegex = null;

        if ($commitishPathRegex === null) {
            $commitishPathRegex = '.+';
        }

        return $commitishPathRegex;
    }

    public function getRepositoryRegex()
    {
        static $regex = null;
        if ($regex === null) {
            $quotedPaths = array_map(
                function ($repo) {
                    return preg_quote($repo['name'], '#');
                },
                $this->app['git']->getRepositories($this->app['git.repos'])
            );
            usort(
                $quotedPaths,
                function ($a, $b) {
                    return strlen($b) - strlen($a);
                }
            );
            $regex = implode('|', $quotedPaths);
        }

        return $regex;
    }

    public function isWindows()
    {
        switch (PHP_OS) {
            case 'WIN32':
            case 'WINNT':
            case 'Windows':
                return true;
            default:
                return false;
        }
    }

    /**
     * Strips the base path from a full repository path.
     *
     * @param  string $repoPath Full path to the repository
     *
     * @return string Relative path to the repository from git.repositories
     */
    public function getRelativePath($repoPath)
    {
        if (strpos($repoPath, $this->app['git.repos']) === 0) {
            $relativePath = substr($repoPath, strlen($this->app['git.repos']));

            return ltrim(strtr($relativePath, '\\', '/'), '/');
        }
        throw new \InvalidArgumentException(
                sprintf("Path '%s' does not match configured repository directory", $repoPath)
            );
    }
}
