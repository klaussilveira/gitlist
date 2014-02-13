<?php

namespace GitList\Util;

use Silex\Application;
use GitList\Exception\EmptyRepositoryException;

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
            if ($repository->hasCommit($hash)) {
                $commitish = $hash;
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

            if ($matchedBranch === null) {
                throw new EmptyRepositoryException('This repository is currently empty. There are no commits.');
            }

            $commitish = $matchedBranch;
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
            $app = $this->app;
            $self = $this;
            $quotedPaths = array_map(
               function ($repo) use ($app, $self) {
                    $repoName = $repo['name'];
                    //Windows
                    if ($self->isWindows()){
                       $repoName = str_replace('\\', '\\\\',$repoName);
                    }
                    return $repoName;
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

    public function getEmailRegex()
    {
        static $emailRegex = null;

        if ($emailRegex === null) {
            $emailRegex = '^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$';
            //see lxr.php.net/xref/PHP_5_4/ext/filter/logical_filters.c#525
        }

        return $emailRegex;
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
     * Strips the base path from a full repository path
     *
     * @param  string $repoPath Full path to the repository
     * @return string Relative path to the repository from git.repositories
     */
    public function getRelativePath($repoPath)
    {
        if (strpos($repoPath, $this->app['git.repos']) === 0) {
            $relativePath = substr($repoPath, strlen($this->app['git.repos']));

            return ltrim(strtr($relativePath, '\\', '/'), '/');
        } else {
            throw new \InvalidArgumentException(
                sprintf("Path '%s' does not match configured repository directory", $repoPath)
            );
        }
    }
}

