<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class MainController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('/', function() use ($app) {
            $repositories = $app['git']->getRepositories($app['git.repos']);

            if ($app['categories']) {
                return $this->renderRepositoriesByCategory($app, $repositories);
            } else {
                return $app['twig']->render('index.twig', array(
                    'repositories'   => $repositories,
                ));
            }
        })->bind('homepage');


        $route->get('/refresh', function(Request $request) use ($app ) {
            # Go back to calling page
            return $app->redirect($request->headers->get('Referer'));
        })->bind('refresh');

        $route->get('{repo}/stats/{branch}', function($repo, $branch) use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

            if ($branch === null) {
                $branch = $repository->getHead();
            }

            $stats = $repository->getStatistics($branch);
            $authors = $repository->getAuthorStatistics($branch);

            return $app['twig']->render('stats.twig', array(
                'repo'           => $repo,
                'branch'         => $branch,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
                'stats'          => $stats,
                'authors'        => $authors,
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->value('branch', null)
          ->convert('branch', 'escaper.argument:escape')
          ->bind('stats');

        $route->get('{repo}/{branch}/rss/', function($repo, $branch) use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

            if ($branch === null) {
                $branch = $repository->getHead();
            }

            $commits = $repository->getPaginatedCommits($branch);

            $html = $app['twig']->render('rss.twig', array(
                'repo'           => $repo,
                'branch'         => $branch,
                'commits'        => $commits,
            ));

            return new Response($html, 200, array('Content-Type' => 'application/rss+xml'));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->value('branch', null)
          ->convert('branch', 'escaper.argument:escape')
          ->bind('rss');

        return $route;
    }

    private function renderRepositoriesByCategory(Application $app, $repositories) {
        # Map results by category
        $result = array();
        foreach($repositories as $repository) {
            $category = $repository['category'];
            if(!array_key_exists($category, $result)) {
                $result[$category] = array();
            }
            $result[$category][] = $repository;
        }

        # Sort result by category name
        ksort($result, SORT_NATURAL | SORT_FLAG_CASE);

        # Move empty string entry to last position
        if (array_key_exists('', $result)) {
            $emptyStringArray = $result[''];
            unset($result['']);
            $result[] = $emptyStringArray;
        }

        # Sort repositories by name
        foreach ($result as $resultRepositories) {
            usort($resultRepositories, function($a, $b) {
                return strcmp($a.name, $b.name);
            });
        }

        return $app['twig']->render('index-categories.twig', array(
            'repositoryTree'   => $result,
        ));

    }
}
