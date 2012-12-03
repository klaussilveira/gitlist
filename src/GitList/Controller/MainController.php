<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;

class MainController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('/', function() use ($app) {
/*
            $repositories = array_map(
                function ($repo) use ($app) {
                    $repo['relativePath'] = $app['util.routing']->getRelativePath($repo['path']);
                    return $repo;
                },
                $app['git']->getRepositories($app['git.repos'])
            );
*/

            $repositories = $app['git']->getRepositories($app['git.repos']);
echo "Doing that\n";
            #$repositories = $app['git.repos'];
print_r( $repositories );

            return $app['twig']->render('index.twig', array(
                'repositories'   => $repositories,
            ));
        })->bind('homepage');

        $route->get('{repo}/stats/{branch}', function($repo, $branch) use ($app) {
            #$repository = $app['git']->getRepository($app['git.repos'][$repo]);

            # NOTE: this call is to the ONE Client!
            $repositories = $app['git']->getRepositories($app['git.repos']);
            $path = $repositories[ $repo ]['path'];

            # NOTE: this call is to the OTHER Client!
            $repository = $app['git']->getRepository($path);

            $stats = $repository->getStatistics($branch);
            $authors = $repository->getAuthorStatistics();

            return $app['twig']->render('stats.twig', array(
                'repo'           => $repo,
                'branch'         => $branch,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
                'stats'          => $stats,
                'authors'         => $authors,
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', '[\w-._\/]+')
          ->value('branch', 'master')
          ->bind('stats');

        $route->get('{repo}/{branch}/rss/', function($repo, $branch) use ($app) {
            #$repository = $app['git']->getRepository($app['git.repos'] );

            # NOTE: this call is to the ONE Client!
            $repositories = $app['git']->getRepositories($app['git.repos']);
            $path = $repositories[ $repo ]['path'];

            # NOTE: this call is to the OTHER Client!
            $repository = $app['git']->getRepository($path);

            $commits = $repository->getPaginatedCommits($branch);

            $html = $app['twig']->render('rss.twig', array(
                'repo'           => $repo,
                'branch'         => $branch,
                'commits'        => $commits,
            ));

            return new Response($html, 200, array('Content-Type' => 'application/rss+xml'));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', '[\w-._\/]+')
          ->bind('rss');

        return $route;
    }
}
