<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use GitPrettyStats\Repository;

class StatController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('{repo}/stats/{branch}', function($repo, $branch) use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

            if ($branch === null) {
                $branch = $repository->getHead();
            }

            $statisticsRepository = new Repository($repository->getPath());
            $statisticsRepository->loadCommits();
            $statistics = $statisticsRepository->getStatistics();

            $stats = $repository->getStatistics($branch);
            $authors = $repository->getAuthorStatistics($branch);

            return $app['twig']->render('stats.twig', array(
                'repo'           => $repo,
                'branch'         => $branch,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
                'stats'          => $stats,
                'authors'        => $authors,
                'prettyStats'    => $statistics,
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->value('branch', null)
          ->bind('stats');

          $route->get('{repo}/contributors/{branch}', function($repo, $branch) use ($app) {
              $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

              $statisticsRepository = new Repository($repository->getPath());
              $statisticsRepository->loadCommits();
              $contributors = $statisticsRepository->getCommitsByContributor();

              return $app['twig']->render('contributor.twig', array(
                  'repo'           => $repo,
                  'branch'         => $branch,
                  'branches'       => $repository->getBranches(),
                  'tags'           => $repository->getTags(),
                  'contributors'   => $contributors,
              ));
          })->assert('repo', $app['util.routing']->getRepositoryRegex())
            ->assert('branch', $app['util.routing']->getBranchRegex())
            ->value('branch', null)
            ->bind('contributors');

          $route->get('{repo}/contributor/{email}', function($repo, $email) use ($app) {
              $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

              $statisticsRepository = new Repository($repository->getPath());
              $statisticsRepository->loadCommits();
              $contributors = $statisticsRepository->getCommitsByContributor();

              foreach ($contributors as $contributor) {
                  if ($email === $contributor['email']) {
                    $contributorStatistics[] = $contributor;
                  }
              }

              $branch = $repository->getCurrentBranch();
              $authors = $repository->getAuthorStatistics($branch);

              return $app['twig']->render('contributor.twig', array(
                  'repo'         => $repo,
                  'branch'       => $repository->getCurrentBranch(),
                  'branches'     => $repository->getBranches(),
                  'tags'         => $repository->getTags(),
                  'contributors' => $contributorStatistics,
                  'authors'      => $authors,
                  'email'        => $email,
              ));
          })->assert('repo', $app['util.routing']->getRepositoryRegex())
            ->assert('email', $app['util.routing']->getEmailRegex())
            ->value('email', null)
            ->bind('contributor');

        return $route;
    }
}
