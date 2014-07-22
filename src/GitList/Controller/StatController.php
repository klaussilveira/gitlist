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
                new \Gitter\Statistics\Date,
                new \Gitter\Statistics\Day,
                new \Gitter\Statistics\Hour
            ));
            $statistics = $statisticsRepository->getStatistics();

            // TODO Add conditionals
            // TODO Display date localized
            // Repository statistics
            $commitsForStats = $statistics['date']->getItems();
            $totalCommits = $statistics['date']->count();
            // First and last commits
            reset($commitsForStats);
            $firstCommit = key($commitsForStats);
            end($commitsForStats);
            $lastCommit = key($commitsForStats);
            // Date difference
            $dateInterval = date_diff(date_create($firstCommit), date_create($lastCommit));
            $activeDays = $dateInterval->format('%a');
            // Average commits per day
            $averageCommits = $activeDays > 0 ? ($totalCommits/$activeDays) : 0;

            $repoStatistics = array(
                'Total Commits' => $totalCommits,
                'First Commit' => $firstCommit,
                'Latest Commit' => $lastCommit,
                'Active For' => $activeDays . ' Days',
                'Average Commits Per Day' => number_format($averageCommits, 2),
            );

            // Commits by date
            foreach ($statistics['date'] as $date => $commits) {
                $dates[] = $date;
                $commitsPerDate[] = count($commits);
            }
            $commitsByDate = array (
                'x' => $dates,
                'y' => $commitsPerDate
            );

            // Commits by hour
            foreach ($statistics['hour'] as $hour => $commits) {
                $hours[] = $hour;
                $commitsPerHour[] = count($commits);
            }
            $commitsByHour = array (
                'x' => $hours,
                'y' => $commitsPerHour
            );

            // Commits by day
            $commitsByDay  = array();
            $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');

            foreach ($statistics['day'] as $weekday => $commits) {
                $commitsByDay[] = array($days[$weekday - 1], count($commits));
            }

            $charts = array (
                'date'        => $commitsByDate,
                'hour'        => $commitsByHour,
                'day'         => $commitsByDay
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

              // Not creating a new repository will lead to php class confusion
              // $repository above is a GitList\Repository
              // A Gitter\Repository is needed for statistics
              $client = new \Gitter\Client;
              $statisticsRepository = $client->getRepository($repository->getPath());
              $statisticsRepository->addStatistics(array(
                  new \Gitter\Statistics\Contributors
              ));
              $statistics = $statisticsRepository->getStatistics();

              // Commits per contributor
              $contributors = array ();

              foreach ($statistics['contributors'] as $email => $dates) {
                  $commitDates = $dates->getItems();
                  $x = array();
                  $y = array();
                  $total = 0;

                  foreach($commitDates as $date => $commits) {
                      $totalDaily = count($commits);
                      $x[] = $date;
                      $y[] = $totalDaily;

                      // Add to contributor total
                      $total = $total + $totalDaily;
                  }

                  // Gets the name from the last commit to display
                  end($commitDates);
                  $lastCommitDate = key($commitDates);
                  end($commitDates[$lastCommitDate]);
                  $lastCommit = key($commitDates[$lastCommitDate]);
                  $name = $commitDates[$lastCommitDate][$lastCommit]->getAuthor()->getName();

                  $contributors[] = array (
                      'name' => $name,
                      'email' => $email,
                      'commits' => $total,
                      'x' => $x,
                      'y' => $y
                  );
              }

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

              // Not creating a new repository will lead to php class confusion
              // $repository above is a GitList\Repository
              // A Gitter\Repository is needed for statistics
              $client = new \Gitter\Client;
              $statisticsRepository = $client->getRepository($repository->getPath());
              $statisticsRepository->addStatistics(array(
                  new \Gitter\Statistics\Contributors
              ));
              $statistics = $statisticsRepository->getStatistics();
              $contributorCommits = $statistics['contributors']->getItems()[$email];

              // Commits per contributor
              $categorizedCommits = array ();

              $commitDates = $contributorCommits->getItems();

              foreach ($commitDates as $date => $commits) {
                  $commitDate = $date;
                  $categorizedCommits[$commitDate][] = $commits[0];
              }

              // Sort commits in reverse chronological order
              krsort($categorizedCommits);

              $branch = $repository->getCurrentBranch();
              $authors = $repository->getAuthorStatistics($branch);

              return $app['twig']->render('contributor.twig', array(
                  'repo'         => $repo,
                  'branch'       => $repository->getCurrentBranch(),
                  'branches'     => $repository->getBranches(),
                  'tags'         => $repository->getTags(),
                  'contributors' => 'ignore',
                  'authors'      => $authors,
                  'email'        => $email,
                  'commits'      => $categorizedCommits,
              ));
          })->assert('repo', $app['util.routing']->getRepositoryRegex())
            ->assert('email', $app['util.routing']->getEmailRegex())
            ->value('email', null)
            ->bind('contributor');

        return $route;
    }
}
