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
    # Maximum number of repositories to load in
    const MAX_REPOS = 1000;

    protected $path;
    protected $hidden;

    # Locally cached version of found repositories.
    # Treated like a singleton
    private $repositories = null;

    private $inifile = null;
    private $cached_repos = null;

    public function __construct($options = null)
    {
        if (!isset($options['path'])) {
            $finder = new ExecutableFinder();
            $options['path'] = $finder->find('git', '/usr/bin/git');
        }

        $this->setPath($options['path']);
        $this->setHidden((isset($options['hidden'])) ? $options['hidden'] : array());

        $this->inifile = $options['ini.file'];
        $this->cached_repos = $options['cache.repos'];
    }


    private function handleCached()
    {
        if ($this->checkCached($this->inifile, $this->cached_repos)) {
            # Retrieve cache
            $file = @file_get_contents($this->cached_repos);

            $repos = array();
            if (false !== $file) {
                $repos = json_decode($file, true);
             }

            $this->setRepositories($repos);
            return $repos;
        } else {
            return null;
        }
    }

    /**
     * @return true if dst present and older than src, false otherwise
     *
     */
    public function checkCached($src, $dst)
    {
        # Can happen during unit tests!
        if (!file_exists($src)) {
            return true;
        }

        if (!file_exists($dst)) {
            return false;
        }

        if (filemtime($src) > filemtime($dst)) {
            return false;
        }

        return true;
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

        $retval = $repository->create($bare);

        # NOTE: createRepository() appears to be only called in unit test.
        $this->addRepository($path, true);

        return $retval;
    }


    /**
     * Opens a repository at the specified path
     *
     * Deliberately renamed from getRepository, so that it doesn't conflict
     * with the method of the same name in Client.php in project gitlist.
     *
     * @param  string     $path Path where the repository is located
     * @return Repository Instance of Repository
     */
    public function getRepositoryCached($paths, $repo)
    {
        $repositories = $this->getRepositories($paths);

        if (!isset($repositories[$repo])) {
            throw new \RuntimeException("Repository $repo not in the cache list");
        }

        $path = $repositories[$repo]['path'];

        if (in_array($path, $this->getHidden())) {
            throw new \RuntimeException('You don\'t have access to this repository');
        }

        if (!file_exists($path) || !file_exists($path . '/.git/HEAD') && !file_exists($path . '/HEAD')) {
            throw new \RuntimeException('There is no GIT repository at ' . $path);
        }


        return new Repository($path, $this);
    }


    /**
     * Remove the cachefile for the repo list, if present.
     *
     * It will be regenerated on the next call to gitlist.
     */
    public function deleteCached()
    {
        if ($this->cached_repos != null) {
            unlink($this->cached_repos);
        }
    }

    /**
     * Add any repositories in the given path to the cache list.
     *
     * If param savefile == false, then the repository list will not
     * get saved to the cache file. This is useful for running the
     * unit tests.
     */
    private function addRepository($inPath, $savefile = true)
    {
        if (!is_array($inPath)) {
            $paths = array($inPath);
        }

        # Check given path for repo's
        $newRepos = array();

        foreach($paths  as $path) {
            # TODO: check what happens if multiple similar paths are merged.
            $this->recurseDirectory($newRepos, $path);
        }

        if (count($newRepos) > 0) {
            $repos = $this->getRepositories(null);

            # Detect new repo's
            # NOTE: This will not detect new repo's with same
            #       name as previous repo.
            $changed = false;
            foreach ($newRepos as $repoName => $newRepo) {
                if (!isset($repos[ $repoName])) {
                    $changed = true;
                    break;
                }
            }

            $repos = array_merge( $repos, $newRepos);

            # Save changes if specified and possible
            if ($changed && $savefile && ( $this->cached_repos != null )) {
                file_put_contents($this->cached_repos, json_encode($repos));
            }

            $this->repositories = $repos;
        }
    }


    /**
     * Searches for valid repositories on the specified path
     *
     * @param  string $path Path where repositories will be searched
     *
     * @return array  Found repositories, containing their name, path and description
     */
    public function getRepositories($paths)
    {
        # If repo list already loaded, use that.
        $repos = $this->repositories;

        if ($repos == null) {
            # Try loading from cache
            $repos = $this->handleCached();
        }

        if ($repos != null) {
            ksort($repos);
            return $repos;
        }

        $repos = array();

        # If no paths specified, don't bother searching them
        if ($paths == null) return $repos;


        #
        # No cache file either, create repo list from scratch
        #

        if (!is_array($paths)) {
            $paths = array($paths);
        }

        foreach($paths  as $path) {
            # TODO: check what happens if multiple similar paths are merged.
            $this->recurseDirectory($repos, $path);
        }

        if (empty($repos)) {
            throw new \RuntimeException('There are no GIT repositories in ' . $path);
        }

        ksort($repos);

        // Store to cache file, if possible
        if ($this->cached_repos) {
            file_put_contents($this->cached_repos, json_encode($repos));
        }

        $this->repositories = $repos;

        return $repos;
    }


    public function setRepositories($repositories)
    {
        $this->repositories = $repositories;
    }


    private static function endsWith($str, $test)
    {
        return (substr_compare($str, $test, -strlen($test), strlen($test)) === 0);
    }


    #
    # Checks current directory first, then moves on to subdirectories
    #
    # Variable repositories intentionally passed by reference, so that
    # a test can be performed on too many repo's. This is a way of putting
    # a limit on recursion.
    #
    private function recurseDirectory(&$repositories, $path)
    {
        if (count($repositories) > self::MAX_REPOS) {
            echo "Too many repo's found, not recursing further.\n";
            return;
        }

        # Paranoia check; don't recurse into git directories
        if (self::endsWith($path, ".git") || self::endsWith($path, "HEAD")) {
            #echo "Not doing git directories!\n";
            return;
        }

        if (in_array($path, $this->getHidden())) {
            #echo "Skipping configured hidden.";
            return;
        }

        $dir = new \DirectoryIterator($path);

        $isRepository = false;
        $isBare = false;
        $cur_path = "";

        # Preprocess returned directories
        $recurse = array();
        foreach ($dir as $file) {
            $filename = $file->getFilename();

            if (!$file->isDir()) continue;
            if (!$file->isReadable()) continue;
            if ($filename === "..") continue;   # Skip parent
            if ((in_array($file->getPathname(), $this->getHidden()))) continue;  # Skip files configured as hidden

            if ($filename === ".") {
                $isBare = file_exists($file->getPathname() . '/HEAD');
                $isRepository = file_exists($file->getPathname() . '/.git/HEAD');
                $cur_path = $file->getPathname();

                continue;
            }

            # Skip hidden files & dir's
            if (strrpos($file->getFilename(), '.') === 0) {
                continue;
            }

            $recurse [] = $file->getPathname();
        }

        if ($isRepository || $isBare) {

            $tmp = array_reverse(explode(DIRECTORY_SEPARATOR, rtrim($path, DIRECTORY_SEPARATOR)));
            $filename = $tmp[0];
            # Pathological case: '/' was defined as root
            if ( $filename == '') $filename = 'root';

            if ($isBare) {
                $description = $cur_path . '/description';
            } else {
                $description = $cur_path . '/.git/description';
            }

            if (file_exists($description)) {
                $description = file_get_contents($description);
            } else {
                $description = 'There is no repository description file. Please, create one to remove this message.';
            }


            $repositories[$filename] = array(
                'name' => $filename,
                'relativePath' => $cur_path,        # TODO: Fix
                'path' => $cur_path,
                'description' => $description
            );
        }

        foreach ($recurse as $item) {
            $this->recurseDirectory($repositories, $item);
        }
    }


    public function run($repository, $command)
    {
        if (version_compare($this->getVersion(), '1.7.2', '>=')) {
            $command = '-c "color.ui"=false ' . $command;
        }

        $process = new Process($this->getPath() . ' ' . $command, $repository->getPath());
        $process->setTimeout(180);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    public function getVersion()
    {
        $process = new Process($this->getPath() . ' --version');
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $version = substr($process->getOutput(), 12);
        return trim($version);
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

