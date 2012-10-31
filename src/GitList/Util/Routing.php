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

    public function getRepositoryRegex()
    {
        static $regex = null;

        if ($regex === null) {
            $app = $this->app;
            $quoted_paths = array_map(
                function ($repo) use ($app) {
                    return preg_quote($app['util.routing']->getRelativePath($repo['path']), '#');
                },
                $this->app['git']->getRepositories($this->app['git.repos'])
            );
            $regex = '/' . implode('|', $quoted_paths) . '/';
        }

        return $regex;
    }

    /**
     * Strips the base path from a full repository path
     *
     * @param string $repo_path Full path to the repository
     * @return string Relative path to the repository from git.repositories
     */
    public function getRelativePath($repo_path)
    {
        if (strpos($repo_path, $this->app['git.repos']) === 0) {
            $relative_path = substr($repo_path, strlen($this->app['git.repos']));
            return ltrim($relative_path, '/');
        } else {
            throw new \InvalidArgumentException(
                sprintf("Path '%s' does not match configured repository directory", $repo_path)
            );
        }
    }
}
