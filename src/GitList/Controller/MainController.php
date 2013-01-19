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
            $repositories = $app['git']->getRepositories($app['git.repos']);

            return $app['twig']->render('index.twig', array(
                'repositories'   => $repositories,
            ));
        })->bind('homepage');

        $route->get('{repo}/stats/{branch}', function($repo, $branch) use ($app) {
            $repotmp = $app['git']->getRepositoryCached($app['git.repos'], $repo);

            $repository = $app['git']->getRepository($repotmp->getPath());

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
          ->assert('branch', '[\w-._\/]+')
          ->value('branch', null)
          ->bind('stats');

        $route->get('{repo}/{branch}/rss/', function($repo, $branch) use ($app) {
            $repotmp = $app['git']->getRepositoryCached($app['git.repos'], $repo);
            $repository = $app['git']->getRepository($repotmp->getPath());

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
