<?php

namespace GitList\Util;

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
     */
    public function parseCommitishPathParam($commitishPath, $repo)
    {
        $app = $this->app;
        $repository = $app['git']->getRepository($app['git.repos'], $repo);

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
            // DEBUG Can you have a repo with no branches? How should we handle
            // that?
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

            $commitish = $matchedBranch;
        }

        if ($commitish === null) {
            $app->abort(404, "'$branch_path' does not appear to contain a commit-ish for '$repo'.");
        }

        $commitishLength = strlen($commitish);
        $path = substr($commitishPath, $commitishLength);
        if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }

        $commitHasPath = $repository->pathExists($commitish, $path);
        /*if ($commitHasPath !== true) {
            $app->abort(404, "\"$path\" does not exist in \"$commitish\".");
        }*/

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
                    $repoName =  $repo['name'] ;
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


    public function isWindows()
    {
      switch(PHP_OS){
        case  'WIN32':
        case  'WINNT':
        case  'Windows': return true;
        default : return false;
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

