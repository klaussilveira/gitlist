<?php

namespace GitList\Git;

use GitList\Git\Client as BaseClient;
use GitList\Exception\NoRepositoryException;

class GitoliteClient extends BaseClient
{
    protected $gitoliteWrapperPath;
    protected $username;

    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->gitoliteWrapperPath = $options['gitolite.wrapper_path'];
        $this->username = $options['username'];
    }

    /**
     * Filter accessible repositories for a user.
     *
     * @param  array $paths Array of paths where repositories will be searched
     * @return array Found repositories, containing their name, path and description sorted
     *               by repository name
     */
    public function getRepositories($paths)
    {
        $allRepositories = parent::getRepositories($paths);

        if(sizeof($paths) !== 1) {
            throw new \RuntimeException('Gitolite requires only one root repo directory.');
        }

        $path = $paths[0];
        $cmd = $this->gitoliteWrapperPath ."  '". $this->username ."'";
        $output = shell_exec($cmd);

        if($output) {
            $output = json_decode($output, true);
        }

        if($output) {
            if(!isset($output['repos'])) {
                $allRepositories = array();
            }

            foreach($allRepositories as $repository => $options) {
                if(mb_substr($path, -1) !== DIRECTORY_SEPARATOR) {
                    $path .= DIRECTORY_SEPARATOR;
                }

                $repositoryName = preg_replace('/^' . preg_quote($path, '/') . '/', '', $options['path']);
                $repositoryName = preg_replace('/\.git$/', '', $repositoryName);

                if(!isset($output['repos'][$repositoryName]) || !isset($output['repos'][$repositoryName]['perms']['R']) || $output['repos'][$repositoryName]['perms']['R'] !== 1) {
                    unset($allRepositories[$repository]);
                }
            }
        } else {
            throw new \RuntimeException('There is a problem getting repo info.');
        }

        if (empty($allRepositories)) {
            throw new NoRepositoryException('No repository is accessible to you.');
        }

        return $allRepositories;
    }
}

