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
            $repositories = $app['git']->getRepositories($app['git.repos'], true);
            $directories = $app['git']->getDirectories($app['git.repos'], true);

            return $app['twig']->render('index.twig', array(
                'base' => '',
                'directories' => $directories,
                'repositories' => $repositories,
            ));
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

            $stats = $repository->getStatistics($branch);
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

        $route->get('{directory}/', function ($directory) use ($app) {
            $searchDirectories = array_map(
                function ($baseDirectory) use ($directory) {
                    return $baseDirectory . DIRECTORY_SEPARATOR . $directory;
                },
                $app['git.repos']
            );
            $repositories = $app['git']->getRepositories($searchDirectories, true);
            $directories = $app['git']->getDirectories($searchDirectories, true);

            $base = '';
            $breadcrumbs = array_map(
                function ($part) use (&$base) {
                    $breadcrumb = array('path' => $base . $part, 'dir' => $part);
                    $base = $base . $part . DIRECTORY_SEPARATOR;
                    return $breadcrumb;
                },
                explode(DIRECTORY_SEPARATOR, $directory)
            );

            return $app['twig']->render('index.twig', array(
                'base' => $directory . DIRECTORY_SEPARATOR,
                'breadcrumbs' => $breadcrumbs,
                'directories' => $directories,
                'repositories' => $repositories,
            ));
        })->assert('directory', $app['util.routing']->getDirectoryRegex())
          ->bind('directory');

        return $route;
    }
}
