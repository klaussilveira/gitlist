<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


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

            $stats = $repository->getStatistics($branch);
            $authors = $repository->getAuthorStatistics($branch);

            // Not creating a new repository will lead to php class confusion
            // $repository above is a GitList\Repository
            // A Gitter\Repository is needed for statistics
            $client = new \Gitter\Client;
            $statisticsRepository = $client->getRepository($repository->getPath());
            $statisticsRepository->addStatistics(array(
                // new \Gitter\Statistics\Contributors,
                new \Gitter\Statistics\Date,
                new \Gitter\Statistics\Day,
                new \Gitter\Statistics\Hour
            ));
            $statistics = $statisticsRepository->getStatistics();
            // echo '<pre>'; 
            // print_r($statistics);
            // echo '</pre>';
            // $hour = $statistics['hour']->getItems();
            // $h = array_pop($hour);
            // print_r($hour[00][0]->getShortHash());
            // $statistics['day']
            // 
            $repoStatistics = array();

            // Commits by date
            foreach ($statistics['date'] as $date => $commits) {
                $dates[] = $date;
                $commitsPerDate[] = count($commits);
            }
            $commitsByDate         = array (
                'x' => $dates,
                'y' => $commitsPerDate
            );

            // Commits by hour
            foreach ($statistics['hour'] as $hour => $commits) {
                $hours[] = $hour;
                $commitsPerHour[] = count($commits);
            }
            $commitsByHour         = array (
                'x' => $hours,
                'y' => $commitsPerHour
            );

            // Commits by day
            $commitsByDay          = array();
            $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');

            foreach ($statistics['day'] as $weekday => $commits) {
                $commitsByDay[] = array($days[$weekday - 1], count($commits));
            }

            // Needed here? Commits per contributor
            $commitsPerContributor = array ();

            $charts                = array (
                'date'        => $commitsByDate,
                'hour'        => $commitsByHour,
                'day'         => $commitsByDay,
                'contributor' => $commitsPerContributor
            );

            $prettyStats = array ( 
              'statistics' => $repoStatistics,
              'charts'     => $charts
            );

            return $app['twig']->render('stats.twig', array(
                'repo'           => $repo,
                'branch'         => $branch,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
                'stats'          => $stats,
                'authors'        => $authors,
                'prettyStats'    => $prettyStats,
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
                  'commits' => null,
              ));
          })->assert('repo', $app['util.routing']->getRepositoryRegex())
            ->assert('branch', $app['util.routing']->getBranchRegex())
            ->value('branch', null)
            ->bind('contributors');

          $route->get('{repo}/contributor/{email}', function($repo, $email) use ($app) {
              $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

              $statisticsRepository = new Repository($repository->getPath());
              $statisticsRepository->loadCommits();
              $contributorStatistics = $statisticsRepository->getCommitsByContributor($email);

              $categorized = array();

              foreach ($contributorStatistics[0]['hashes'] as $commit) {
                  $date = $commit[0]->getDate();
                  $date = $date->format('m/d/Y');
                  $categorized[$date][] = $commit[0];
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
                  'commits'      => $categorized,
              ));
          })->assert('repo', $app['util.routing']->getRepositoryRegex())
            ->assert('email', $app['util.routing']->getEmailRegex())
            ->value('email', null)
            ->bind('contributor');

        return $route;
    }
}
