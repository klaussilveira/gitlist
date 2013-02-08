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
            $repositories = array_map(
                function ($repo) use ($app) {
                    $repo['relativePath'] = $app['util.routing']->getRelativePath($repo['path']);
                    $repository = $app['git']->getRepository($repo['path']);
                    $repo['branch'] = $repository->getHead();

                    return $repo;
                },
                $app['git']->getRepositories($app['git.repos'])
            );

            return $app['twig']->render('index.twig', array(
                'repositories'   => $repositories,
            ));
        })->bind('homepage');

        $route->get('{repo}/stats/{branch}', function($repo, $branch) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            if ($branch === null) {
                $branch = $repository->getHead();
            }
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
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->value('branch', null)
          ->bind('stats');

        $route->get('{repo}/{branch}/rss/', function($repo, $branch) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            $commits = $repository->getPaginatedCommits($branch);

            $html = $app['twig']->render('rss.twig', array(
                'repo'           => $repo,
                'branch'         => $branch,
                'commits'        => $commits,
            ));

            return new Response($html, 200, array('Content-Type' => 'application/rss+xml'));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->bind('rss');

        return $route;
    }
}
