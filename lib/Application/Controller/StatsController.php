<?php

namespace Application\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;

class StatsController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('{repo}/stats/{branch}', function($repo, $branch) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
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
        })->assert('repo', '[\w-._]+')
          ->assert('branch', '[\w-._]+')
          ->value('branch', 'master')
          ->bind('stats');

        return $controllers;
    }
}
