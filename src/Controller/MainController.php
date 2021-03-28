<?php

namespace GitList\Controller;

use Silex\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MainController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('/', function () use ($app) {
            $repositories = $app['git']->getRepositories($app['git.repos']);
            $categories = array();
            $noCategory = array();

            $arrLen = count($repositories);

            foreach($repositories as &$repo) {
	        if(!is_null($repo["category"])){
                    if(!array_key_exists($repo["category"], $categories)){
                        $categories[$repo["category"]] = array();
                    }

                    $categories[$repo["category"]][] = $repo;
                } else {
                    $noCategory[] = $repo;
                }
            }

	  // print count($noCategory);
	  // print count($categories);
            uksort($categories, function ($k1, $k2) {
                return strtolower($k2) < strtolower($k1);
            });


            return $app['twig']->render('index.twig', array( 'noCategory' => $noCategory, 'categories' => $categories ));
        })->bind('homepage');

        $route->get('/refresh', function (Request $request) use ($app) {
            // Go back to calling page
            return $app->redirect($request->headers->get('Referer'));
        })->bind('refresh');

        $route->get('{repo}/stats/{branch}', function ($repo, $branch) use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

            if ($branch === null) {
                $branch = $repository->getHead();
            }

            $stats = $repository->getBranchStatistics($branch);
            $authors = $repository->getAuthorStatistics($branch);

            return $app['twig']->render('stats.twig', array(
                'repo' => $repo,
                'branch' => $branch,
                'branches' => $repository->getBranches(),
                'tags' => $repository->getTags(),
                'stats' => $stats,
                'authors' => $authors,
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->value('branch', null)
          ->convert('branch', 'escaper.argument:escape')
          ->bind('stats');

        $route->get('{repo}/{branch}/rss/', function ($repo, $branch) use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

            if ($branch === null) {
                $branch = $repository->getHead();
            }

            $commits = $repository->getPaginatedCommits($branch);

            $html = $app['twig']->render('rss.twig', array(
                'repo' => $repo,
                'branch' => $branch,
                'commits' => $commits,
            ));

            return new Response($html, 200, array('Content-Type' => 'application/rss+xml'));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->value('branch', null)
          ->convert('branch', 'escaper.argument:escape')
          ->bind('rss');

        return $route;
    }
}
