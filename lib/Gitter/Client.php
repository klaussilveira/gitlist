<?php

/*
 * This file is part of the Gitter library.
 *
 * (c) Klaus Silveira <klaussilveira@php.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gitter;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;

class Client
{
    protected $path;
    protected $hidden;

    public function __construct($options = null)
    {
        if (!isset($options['path'])) {
            $finder = new ExecutableFinder();
            $options['path'] = $finder->find('git', '/usr/bin/git');
        }
        $this->setPath($options['path']);
        $this->setHidden((isset($options['hidden'])) ? $options['hidden'] : array());
    }

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
     * Opens a repository at the specified path
     *
     * @param  string     $path Path where the repository is located
     * @return Repository Instance of Repository
     */
    public function getRepository($path)
    {
        if (!file_exists($path) || !file_exists($path . '/.git/HEAD') && !file_exists($path . '/HEAD')) {
            throw new \RuntimeException('There is no GIT repository at ' . $path);
        }

        if (in_array($path, $this->getHidden())) {
            throw new \RuntimeException('You don\'t have access to this repository');
        }

        return new Repository($path, $this);
    }

    /**
     * Searches for valid repositories on the specified path
     *
     * @param  string $path Path where repositories will be searched
     * @return array  Found repositories, containing their name, path and description
     */
    public function getRepositories($path)
    {
        $repositories = $this->recurseDirectory($path);

        if (empty($repositories)) {
            throw new \RuntimeException('There are no GIT repositories in ' . $path);
        }

        sort($repositories);

        return $repositories;
    }

    private function recurseDirectory($path)
    {
        $dir = new \DirectoryIterator($path);

        $repositories = array();

        foreach ($dir as $file) {
            if ($file->isDot()) {
                continue;
            }

            if (strrpos($file->getFilename(), '.') === 0) {
                continue;
            }

            if ($file->isDir()) {
                $isBare = file_exists($file->getPathname() . '/HEAD');
                $isRepository = file_exists($file->getPathname() . '/.git/HEAD');

                if ($isRepository || $isBare) {
                    if (in_array($file->getPathname(), $this->getHidden())) {
                        continue;
                    }

                    if ($isBare) {
                        $description = $file->getPathname() . '/description';
                    } else {
                        $description = $file->getPathname() . '/.git/description';
                    }

                    if (file_exists($description)) {
                        $description = file_get_contents($description);
                    } else {
                        $description = 'There is no repository description file. Please, create one to remove this message.';
                    }

                    $repositories[] = array('name' => $file->getFilename(), 'path' => $file->getPathname(), 'description' => $description);
                    continue;
                }
            }
        }

        return $repositories;
    }

    public function run($repository, $command)
    {
        $process = new Process($this->getPath() . ' -c "color.ui"=false ' . $command, $repository->getPath());
        $process->setTimeout(180);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * Get the current Git binary path
     *
     * @return string Path where the Git binary is located
     */
    protected function getPath()
    {
        return $this->path;
    }

    /**
     * Set the current Git binary path
     *
     * @param string $path Path where the Git binary is located
     */
    protected function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get hidden repository list
     *
     * @return array List of repositories to hide
     */
    protected function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the hidden repository list
     *
     * @param array $hidden List of repositories to hide
     */
    protected function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }
}
