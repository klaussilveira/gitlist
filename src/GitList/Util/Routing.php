<?php

namespace Gitlist\Util;

use Silex\Application;

class Routing
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /* @brief Return $commitish, $path parsed from $commitish_path, based on
     * what's in $repo. Raise a 404 if $branchpath does not represent a
     * valid branch and path.
     *
     * A helper for parsing routes that use commit-ish names and paths
     * separated by /, since route regexes are not enough to get that right.
     */
    public function parseCommitishPathParam($commitish_path, $repo) {
        $app = $this->app;
        $repository = $app['git']->getRepository($app['git.repos'] . $repo);

        $commitish = null;
        $path = null;

        $slash_pos = strpos($commitish_path, '/');
        if (strlen($commitish_path) >= 40 &&
            ($slash_pos === FALSE ||
             $slash_pos === 40)) {
            // We may have a commit hash as our commitish.
            $hash = substr($commitish_path, 0, 40);
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

            $matched_branch = null;
            $matched_branch_name_len = 0;
            foreach ($branches as $branch) {
                if (strpos($commitish_path, $branch) === 0 &&
                    strlen($branch) > $matched_branch_name_len) {
                    $matched_branch = $branch;
                    $matched_branch_name_len = strlen($matched_branch);
                }
            }

            $commitish = $matched_branch;
        }

        if ($commitish === null) {
            $app->abort(404, "'$branch_path' does not appear to contain a " .
                             "commit-ish for '$repo.'");
        }

        $commitish_len = strlen($commitish);
        $path = substr($commitish_path, $commitish_len);
        if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }

        $commit_has_path = $repository->pathExists($commitish, $path);
        if ($commit_has_path !== TRUE) {
            $app->abort(404, "\"$path\" does not exist in \"$commitish\".");
        }

        return array($commitish, $path);
    }

    public function getBranchRegex() {
        static $branch_regex = null;

        if ($branch_regex === null) {
            $branch_regex = '[\w-._\/]+';
        }

        return $branch_regex;
    }

    public function getCommitishPathRegex() {
        static $commitish_path_regex = null;

        if ($commitish_path_regex === null) {
            $commitish_path_regex = '.+';
        }

        return $commitish_path_regex;
    }

    public function getRepositoryRegex()
    {
        static $regex = null;

        if ($regex === null) {
            $app = $this->app;
            $quotedPaths = array_map(
                function ($repo) use ($app) {
                    return preg_quote($app['util.routing']->getRelativePath($repo['path']), '#');
                },
                $this->app['git']->getRepositories($this->app['git.repos'])
            );
            usort($quotedPaths, function ($a, $b) { return strlen($b) - strlen($a); });
            $regex = implode('|', $quotedPaths);
        }

        return $regex;
    }

    /**
     * Strips the base path from a full repository path
     *
     * @param string $repoPath Full path to the repository
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
