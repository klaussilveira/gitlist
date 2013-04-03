<?php

namespace GitList\Git;

use Gitter\Client as BaseClient;

class Client extends BaseClient
{
    /**
     * Creates a new repository on the specified path
     *
     * @param  string     $path Path where the new repository will be created
     * @return Repository Instance of Repository
     */
    public function createRepository($path, $bare = null)
    {
        if (file_exists($path . '/.git/HEAD') && !file_exists($path . '/HEAD')) {
            throw new \RuntimeException('A GIT repository already exists at ' . $path);
        }

        $repository = new Repository($path, $this);

        return $repository->create($bare);
    }

    /**
     * Opens a specified repository
     *
     * @param  array      $repos Array of items describing configured repositories
     * @param  string     $repo  Name of repository we are currently handling
     * @return Repository Instance of Repository
     */
    public function getRepository($repos, $repo)
    {
        $repotmp = $this->getRepositoryCached($repos, $repo);
        $path = $repotmp->getPath();

        if (!file_exists($path) || !file_exists($path . '/.git/HEAD') && !file_exists($path . '/HEAD')) {
            throw new \RuntimeException('There is no GIT repository at ' . $path);
        }

        if (in_array($path, $this->getHidden())) {
            throw new \RuntimeException('You don\'t have access to this repository');
        }

        return new Repository($path, $this);
    }
}

