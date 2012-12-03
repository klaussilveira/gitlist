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
    public function getRepository($paths, $repo)
    {
echo "this getRepository\n";

        $repositories = getRepositories($paths);
        $path = $repositories[ $repo ]['path'];

        if (!file_exists($path) || !file_exists($path . '/.git/HEAD') && !file_exists($path . '/HEAD')) {
            throw new \RuntimeException('There is no GIT repository at ' . $path);
        }

        if (in_array($path, $this->getHidden())) {
            throw new \RuntimeException('You don\'t have access to this repository');
        }

        return new Repository($path, $this);
    }


    private $repositories = null;

    /**
     * Searches for valid repositories on the specified path
     *
     * @param  string $path Path where repositories will be searched
     * @return array  Found repositories, containing their name, path and description
     */
    public function getRepositories($paths)
    {
        if ( $repositories != null ) return $this->repositories;

        if ( !is_array( $paths ) ) {
            $paths = array($paths);
        }

        $repositories = array();
        foreach( $paths  as $path ) {
            # TODO: check what happens if multiple similar paths are merged.
            $this->recurseDirectory($repositories, $path);
        }

        if (empty($repositories)) {
            #throw new \RuntimeException('There are no GIT repositories in ' . $path);
        }

        ksort($repositories);

#echo "after search:";
#print_r( $repositories );
        $hits->repositories = $repositories;

        return $repositories;
    }


    private static function endsWith( $str, $test ) {
        return ( substr_compare($str, $test, -strlen($test), strlen($test)) === 0 );
    }


    #
    # Checks current directory first, then moves on to subdirectories
    #
    private function recurseDirectory(&$repositories, $path) {
        if ( count( $repositories ) > 1000 ) {
            echo "Too many repo's found, not recursing further.\n";
            return;
        }

        # Paranoia check; don't recurse into git directories
        if ( self::endsWith( $path, ".git") || self::endsWith( $path, "HEAD") ) {
echo "Not doing git directories!\n";
            return;
        }

        if ( (in_array($path, $this->getHidden())) ) { 
echo "Skipping configured hidden.";
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
            if ( !$file->isReadable() ) continue;
            if ( $filename === "..") continue;   # Skip parent
            if ( (in_array($file->getPathname(), $this->getHidden())) ) continue;  # Skip files configured as hidden

            if ( $filename === ".") {
                $isBare = file_exists($file->getPathname() . '/HEAD');
                $isRepository = file_exists($file->getPathname() . '/.git/HEAD');
                $cur_path = $file->getPathname();

#if ( $isRepository || $isBare ) {
#echo "found repo! {$path}\n";
#echo "found repo! {$file->getPathname()}\n";
#}
                continue;
            }

            # Skip hidden files & dir's 
            if (strrpos($file->getFilename(), '.') === 0) {
                continue;
            }

            $recurse [] = $file->getPathname();
        }

         if ( $isRepository || $isBare ) {

            $tmp = array_reverse( explode('/', rtrim($path, DIRECTORY_SEPARATOR) ));
            $filename = $tmp[0];
            # Pathological case: '/' was defined as root
            if ( $filename == '' ) $filename = 'root';

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
#echo "recursing $item\n";
            $this->recurseDirectory($repositories, $item );
        }
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
