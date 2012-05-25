<?php

namespace Git;

class Client
{
    protected $path;

    public function __construct($path)
    {
        $this->setPath($path);
    }

    /**
     * Creates a new repository on the specified path
     *
     * @param string $path Path where the new repository will be created
     * @return Repository Instance of Repository
     */
    public function createRepository($path)
    {
        if (file_exists($path . '/.git/HEAD') && !file_exists($path . '/HEAD')) {
            throw new \RuntimeException('A GIT repository already exists at ' . $path);
        }

        $repository = new Repository($path, $this);
        return $repository->create();
    }

    /**
     * Opens a repository at the specified path
     *
     * @param string $path Path where the repository is located
     * @return Repository Instance of Repository
     */
    public function getRepository($path)
    {
        if (!file_exists($path) || !file_exists($path . '/.git/HEAD') && !file_exists($path . '/HEAD')) {
            throw new \RuntimeException('There is no GIT repository at ' . $path);
        }

        return new Repository($path, $this);
    }

    /**
     * Searches for valid repositories on the specified path
     *
     * @param string $path Path where repositories will be searched
     * @return array Found repositories, containing their name, path and description
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

            $isBare = file_exists($file->getPathname() . '/HEAD');
            $isRepository = file_exists($file->getPathname() . '/.git/HEAD');

            if ($file->isDir() && $isRepository || $isBare) {
                if ($isBare) {
                    $description = file_get_contents($file->getPathname() . '/description');
                } else {
                    $description = file_get_contents($file->getPathname() . '/.git/description');
                }

                $repositories[] = array('name' => $file->getFilename(), 'path' => $file->getPathname(), 'description' => $description);
                continue;
            }
        }

        return $repositories;
    }

    /**
     * Execute a git command on the repository being manipulated
     *
     * This method will start a new process on the current machine and
     * run git commands. Once the command has been run, the method will
     * return the command line output.
     *
     * @param Repository $repository Repository where the command will be run
     * @param string $command Git command to be run
     * @return string Returns the command output
     */
    public function run(Repository $repository, $command)
    {
        $descriptors = array(0 => array("pipe", "r"), 1 => array("pipe", "w"));
        $process = proc_open($this->getPath() . ' ' . $command, $descriptors, $pipes, $repository->getPath());

        if (is_resource($process)) {
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            proc_close($process);
            return $stdout;
        }
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
    }
}
