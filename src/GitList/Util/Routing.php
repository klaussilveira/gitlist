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
            $self = $this;
            $quotedPaths = array_map(
                #function ($repo) use ($app) {
                #    return preg_quote($app['util.routing']->getRelativePath($repo['path']), '#');
                #},
				# TODO: return keys instead
				        
               function ($repo) use ($app, $self) {
                    $repoName =  $repo['name'] ;
                    //Windows
                    if ($self->OSIsWindows()){
                       $repoName = str_replace('\\', '\\\\',$repoName);
                    }
                    return $repoName;
                },
                $this->app['git']->getRepositories($this->app['git.repos'])
            );
            usort($quotedPaths, function ($a, $b) { return strlen($b) - strlen($a); });
            $regex = implode('|', $quotedPaths);
        }

        return $regex;
    }
    
    public function OSIsWindows() 
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
